/**
 * Keep the reusable comments component in sync with WordPress comment-reply.js.
 */
(function () {
	'use strict';

	function hasDirectReplyForm(commentBody) {
		return Array.prototype.some.call(commentBody.children, function (child) {
			return child.classList.contains('comment-respond');
		});
	}

	function syncReplyFormState(comments) {
		comments.querySelectorAll('.comment-body').forEach(function (commentBody) {
			commentBody.classList.toggle('has-reply-form', hasDirectReplyForm(commentBody));
		});
	}

	function initComments(comments) {
		syncReplyFormState(comments);

		comments.addEventListener('click', function (event) {
			if (!event.target.closest('.comment-reply-link, #cancel-comment-reply-link')) {
				return;
			}

			window.requestAnimationFrame(function () {
				syncReplyFormState(comments);
			});
		});

		if ('MutationObserver' in window) {
			new MutationObserver(function () {
				syncReplyFormState(comments);
			}).observe(comments, { childList: true, subtree: true });
		}
	}

	document.querySelectorAll('[data-cn-comments]').forEach(initComments);
})();
