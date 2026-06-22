<?php
/**
 * Contact page ported from the Shopify contact-page section.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$status      = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$contact_url = get_permalink() ? get_permalink() : home_url( '/contact/' );
?>
<section
	class="contact-page section section--page-width color-scheme-1"
	style="--contact-page-padding-block-start: 48px; --contact-page-padding-block-end: 120px;"
>
	<div class="contact-page__inner">
		<div class="contact-page__header">
			<p class="contact-page__eyebrow"><?php esc_html_e( 'Get in touch', 'simms-research' ); ?></p>
			<h1 class="contact-page__title"><?php esc_html_e( 'Contact Simms', 'simms-research' ); ?></h1>
			<div class="contact-page__intro">
				<p><?php esc_html_e( 'Have a question about an order, product details, or compound research? Send a message and we will route it to the right place.', 'simms-research' ); ?></p>
			</div>
		</div>

		<div class="contact-page__grid">
			<aside class="contact-page__details" aria-label="<?php esc_attr_e( 'Contact details', 'simms-research' ); ?>">
				<div class="contact-page__card contact-page__card--primary contact-page__card--contact">
					<p class="contact-page__label"><?php esc_html_e( 'Contact Us', 'simms-research' ); ?></p>
					<p class="contact-page__contact-copy">
						<?php esc_html_e( 'Reach the Simms support team directly.', 'simms-research' ); ?>
					</p>
					<div class="contact-page__contact-methods">
						<a href="mailto:support@simmsresearch.com" class="contact-page__contact-method">
							<span class="contact-page__method-label"><?php esc_html_e( 'Email support', 'simms-research' ); ?></span>
							<span class="contact-page__method-value">support@simmsresearch.com</span>
							<span class="contact-page__method-note"><?php esc_html_e( 'Best for order details, research, and written follow-up.', 'simms-research' ); ?></span>
						</a>
						<a href="tel:4704696088" class="contact-page__contact-method">
							<span class="contact-page__method-label"><?php esc_html_e( 'Call support', 'simms-research' ); ?></span>
							<span class="contact-page__method-value">470-469-6088</span>
							<span class="contact-page__method-note"><?php esc_html_e( 'Best for quick routing during support hours.', 'simms-research' ); ?></span>
						</a>
					</div>
					<p class="contact-page__meta"><?php esc_html_e( 'Most messages receive a response within one business day.', 'simms-research' ); ?></p>
				</div>

				<div class="contact-page__card">
					<p class="contact-page__label"><?php esc_html_e( 'Order Support', 'simms-research' ); ?></p>
					<p class="contact-page__value"><?php esc_html_e( 'Existing orders', 'simms-research' ); ?></p>
					<p class="contact-page__meta"><?php esc_html_e( 'Include your order number for status, shipping, or account questions.', 'simms-research' ); ?></p>
				</div>

				<div class="contact-page__card">
					<p class="contact-page__label"><?php esc_html_e( 'Research / Product Questions', 'simms-research' ); ?></p>
					<p class="contact-page__value"><?php esc_html_e( 'Product questions', 'simms-research' ); ?></p>
					<p class="contact-page__meta"><?php esc_html_e( 'For research, storage, handling, or product-detail questions.', 'simms-research' ); ?></p>
				</div>

				<div class="contact-page__card">
					<p class="contact-page__label"><?php esc_html_e( 'Support Hours', 'simms-research' ); ?></p>
					<p class="contact-page__value"><?php esc_html_e( 'Monday - Friday', 'simms-research' ); ?></p>
					<p class="contact-page__meta"><?php esc_html_e( '9 AM - 5 PM EST', 'simms-research' ); ?></p>
				</div>
			</aside>

			<div class="contact-page__form-panel" data-simms-form-panel>
				<div class="contact-page__confirmation" role="status" aria-live="polite" tabindex="-1" data-simms-confirmation <?php echo 'sent' === $status ? '' : 'hidden'; ?>>
					<span class="contact-page__confirmation-icon" aria-hidden="true">
						<svg viewBox="0 0 20 20" focusable="false" role="presentation">
							<path fill="currentColor" d="M7.7 14.6 3.5 10.4l1.4-1.4 2.8 2.8 7.4-7.4 1.4 1.4-8.8 8.8Z" />
						</svg>
					</span>
					<div>
						<h2 class="contact-page__confirmation-title"><?php esc_html_e( 'Message sent', 'simms-research' ); ?></h2>
						<p><?php esc_html_e( "Thanks for contacting us. We'll get back to you as soon as possible.", 'simms-research' ); ?></p>
						<p><?php esc_html_e( 'We received your message and will route it to the right team.', 'simms-research' ); ?></p>
						<a class="button contact-page__confirmation-button" href="<?php echo esc_url( $contact_url ); ?>">
							<?php esc_html_e( 'Send another message', 'simms-research' ); ?>
						</a>
					</div>
				</div>

				<div data-simms-form-area <?php echo 'sent' === $status ? 'hidden' : ''; ?>>
					<div class="contact-page__form-header">
						<p class="contact-page__label"><?php esc_html_e( 'Send a Message', 'simms-research' ); ?></p>
						<p><?php esc_html_e( 'Required fields are marked.', 'simms-research' ); ?></p>
					</div>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ContactPageForm-wp" accept-charset="UTF-8" class="contact-page__form" data-simms-form="contact">
						<input type="hidden" name="action" value="simms_contact">
						<input name="contact[id]" type="hidden" value="ContactPageForm-wp">
						<?php wp_nonce_field( 'simms_contact', 'simms_contact_nonce' ); ?>

						<div class="contact-page__message contact-page__message--error" role="alert" tabindex="-1" data-simms-error <?php echo 'error' === $status ? '' : 'hidden'; ?>>
							<svg viewBox="0 0 20 20" focusable="false" role="presentation">
								<path fill="currentColor" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm1 11H9v2h2v-2Zm0-8H9v6h2V5Z" />
							</svg>
							<span data-simms-error-text><?php esc_html_e( 'Please check your email and message, then try again.', 'simms-research' ); ?></span>
						</div>

						<div class="contact-page__row">
							<div class="contact-page__field">
								<label for="ContactPageForm-wp-name"><?php esc_html_e( 'Name', 'simms-research' ); ?></label>
								<input
									type="text"
									id="ContactPageForm-wp-name"
									name="contact[name]"
									autocomplete="name"
									value=""
									placeholder="<?php esc_attr_e( 'Your name', 'simms-research' ); ?>"
								>
							</div>

							<div class="contact-page__field">
								<label for="ContactPageForm-wp-email"><?php esc_html_e( 'Email', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
								<input
									type="email"
									id="ContactPageForm-wp-email"
									name="contact[email]"
									autocomplete="email"
									spellcheck="false"
									autocapitalize="off"
									value=""
									aria-required="true"
									placeholder="you@example.com"
									required
								>
							</div>
						</div>

						<div class="contact-page__row">
							<div class="contact-page__field">
								<label for="ContactPageForm-wp-phone"><?php esc_html_e( 'Phone', 'simms-research' ); ?></label>
								<input
									type="tel"
									id="ContactPageForm-wp-phone"
									name="contact[phone]"
									autocomplete="tel"
									pattern="[0-9\-]*"
									value=""
									placeholder="<?php esc_attr_e( 'Optional', 'simms-research' ); ?>"
								>
							</div>

							<div class="contact-page__field">
								<label for="ContactPageForm-wp-topic"><?php esc_html_e( 'Subject', 'simms-research' ); ?></label>
								<select id="ContactPageForm-wp-topic" name="contact[subject]">
									<option value="Order Support"><?php esc_html_e( 'Order Support', 'simms-research' ); ?></option>
									<option value="Research / Product Question"><?php esc_html_e( 'Research / Product Question', 'simms-research' ); ?></option>
									<option value="Wholesale / Partnership"><?php esc_html_e( 'Wholesale / Partnership', 'simms-research' ); ?></option>
									<option value="General Inquiry"><?php esc_html_e( 'General Inquiry', 'simms-research' ); ?></option>
								</select>
							</div>
						</div>

						<div class="contact-page__field">
							<label for="ContactPageForm-wp-body"><?php esc_html_e( 'Message', 'simms-research' ); ?></label>
							<textarea
								rows="9"
								id="ContactPageForm-wp-body"
								name="contact[body]"
								placeholder="<?php esc_attr_e( 'How can we help?', 'simms-research' ); ?>"
							></textarea>
						</div>

						<button type="submit" class="button contact-page__submit">
							<?php esc_html_e( 'Send Message', 'simms-research' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>
<?php
get_footer();
