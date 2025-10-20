<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Forms\Listener;

use OCA\Forms\Constants;
use OCA\Forms\Db\AnswerMapper;
use OCA\Forms\Db\QuestionMapper;
use OCA\Forms\Events\FormSubmittedEvent;
use OCA\Forms\Service\ConfirmationMailService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @implements IEventListener<FormSubmittedEvent>
 */
class ConfirmationEmailListener implements IEventListener {
	public function __construct(
		private ConfirmationMailService $confirmationMailService,
		private AnswerMapper $answerMapper,
		private QuestionMapper $questionMapper,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof FormSubmittedEvent)) {
			return;
		}

		$submission = $event->getSubmission();
		$form = $event->getForm();

		$emailAddress = null;
		$answerSummaries = [];

		$answers = $this->answerMapper->findBySubmission($submission->getId());

		foreach ($answers as $answer) {
			try {
				$question = $this->questionMapper->findById($answer->getQuestionId());
			} catch (DoesNotExistException $e) {
				$this->logger->warning('Question missing while preparing confirmation mail', [
					'formId' => $form->getId(),
					'submissionId' => $submission->getId(),
					'questionId' => $answer->getQuestionId(),
				]);
				continue;
			}

			$questionType = $question->getType();
			$answerText = trim($answer->getText() ?? '');

			if ($emailAddress === null && $answerText !== '' && $this->answerBelongsToEmailField($questionType, (string)$question->getText(), $question->getExtraSettings())) {
				$emailAddress = $answerText;
			}

			if (
				$answerText !== ''
				&& in_array($questionType, [Constants::ANSWER_TYPE_SHORT, Constants::ANSWER_TYPE_LONG, 'email'], true)
			) {
				$answerSummaries[] = [
					'question' => $question->getText(),
					'answer' => $answerText,
				];
			}
		}

		if ($emailAddress === null) {
			return;
		}

		$this->confirmationMailService->send($form, $submission, $emailAddress, $answerSummaries);
	}

	/**
	 * @param array<string, mixed> $extraSettings
	 */
	private function answerBelongsToEmailField(string $questionType, string $questionTitle, array $extraSettings): bool {
		if ($questionType === 'email') {
			return true;
		}

		// Accept short answers with explicit validation set to email
		if ($questionType === Constants::ANSWER_TYPE_SHORT && (($extraSettings['validationType'] ?? null) === 'email')) {
			return true;
		}

		// Accept short answers whose title clearly indicates an email address field
		if ($questionType === Constants::ANSWER_TYPE_SHORT && $this->titleIndicatesEmail($questionTitle)) {
			return true;
		}

		return false;
	}

	private function titleIndicatesEmail(string $title): bool {
		$normalized = mb_strtolower($title);
		// Normalize by removing common separators to make matching more robust
		$collapsed = str_replace(["\t", "\n", "\r", " ", "-", "_", ":", ";", ","], '', $normalized);

		$needles = [
			'email',           // email, eMail, EMAIL
			'emailaddress',    // email address
			'emailadresse',    // German
			'emailadresa',     // variants
			'e-mail',          // original (will match via collapse too)
			'correoelectronico', // Spanish
			'adresseemail',    // French
			'emailid',         // sometimes used
		];

		foreach ($needles as $needle) {
			$n = str_replace(["\t", "\n", "\r", " ", "-", "_", ":", ";", ","], '', mb_strtolower($needle));
			if ($n !== '' && str_contains($collapsed, $n)) {
				return true;
			}
		}

		return false;
	}
}
