<?php
/**
 * Affiliate application page ported from the Shopify affiliate-application-page section.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$status    = isset( $_GET['application'] ) ? sanitize_key( wp_unslash( $_GET['application'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$apply_url = get_permalink() ? get_permalink() : home_url( '/apply/' );
?>
<section
	class="affiliate-application-page section section--page-width color-scheme-1"
	style="--affiliate-application-padding-block-start: 48px; --affiliate-application-padding-block-end: 120px;"
>
	<div class="affiliate-application-page__inner">
		<a class="affiliate-application-page__back" href="<?php echo esc_url( home_url( '/partners/' ) ); ?>">
			<span aria-hidden="true">&larr;</span>
			<?php esc_html_e( 'Back to Affiliate Program', 'simms-research' ); ?>
		</a>

		<header class="affiliate-application-page__header">
			<p class="affiliate-application-page__eyebrow"><?php esc_html_e( 'Affiliate Program', 'simms-research' ); ?></p>
			<h1 class="affiliate-application-page__title"><?php esc_html_e( 'Affiliate Application', 'simms-research' ); ?></h1>
			<div class="affiliate-application-page__intro">
				<p><?php esc_html_e( "Fill out the form below. We'll review your application and get back to you shortly.", 'simms-research' ); ?></p>
			</div>
		</header>

		<?php if ( 'sent' === $status ) : ?>
			<div class="affiliate-application-page__confirmation" role="status" aria-live="polite" tabindex="-1">
				<span class="affiliate-application-page__confirmation-icon" aria-hidden="true">
					<?php echo simms_inline_icon( 'checkmark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<div>
					<h2 class="affiliate-application-page__confirmation-title"><?php esc_html_e( 'Application sent', 'simms-research' ); ?></h2>
					<p><?php esc_html_e( 'We received your affiliate application and will review it shortly.', 'simms-research' ); ?></p>
					<a class="button affiliate-application-page__confirmation-button" href="<?php echo esc_url( $apply_url ); ?>">
						<?php esc_html_e( 'Submit another application', 'simms-research' ); ?>
					</a>
				</div>
			</div>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="AffiliateApplicationForm-wp" accept-charset="UTF-8" class="affiliate-application-page__form">
				<input type="hidden" name="action" value="simms_affiliate_application">
				<input name="contact[id]" type="hidden" value="AffiliateApplicationForm-wp">
				<input name="contact[tags]" type="hidden" value="affiliate-application">
				<input name="contact[subject]" type="hidden" value="Affiliate Program Application">
				<input name="contact[Program]" type="hidden" value="Affiliate Program">
				<?php wp_nonce_field( 'simms_affiliate_application', 'simms_affiliate_nonce' ); ?>

				<?php if ( 'error' === $status ) : ?>
					<div class="affiliate-application-page__message affiliate-application-page__message--error" tabindex="-1" autofocus>
						<?php echo simms_inline_icon( 'error' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Please check the required fields and try again.', 'simms-research' ); ?>
					</div>
				<?php endif; ?>

				<div class="affiliate-application-page__row">
					<div class="affiliate-application-page__field">
						<label for="AffiliateApplicationForm-wp-first-name"><?php esc_html_e( 'First Name', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
						<input
							type="text"
							id="AffiliateApplicationForm-wp-first-name"
							name="contact[First name]"
							autocomplete="given-name"
							placeholder="<?php esc_attr_e( 'First Name', 'simms-research' ); ?>"
							required
						>
					</div>

					<div class="affiliate-application-page__field">
						<label for="AffiliateApplicationForm-wp-last-name"><?php esc_html_e( 'Last Name', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
						<input
							type="text"
							id="AffiliateApplicationForm-wp-last-name"
							name="contact[Last name]"
							autocomplete="family-name"
							placeholder="<?php esc_attr_e( 'Last Name', 'simms-research' ); ?>"
							required
						>
					</div>
				</div>

				<div class="affiliate-application-page__field">
					<label for="AffiliateApplicationForm-wp-email"><?php esc_html_e( 'Email', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
					<input
						type="email"
						id="AffiliateApplicationForm-wp-email"
						name="contact[email]"
						autocomplete="email"
						spellcheck="false"
						autocapitalize="off"
						aria-required="true"
						placeholder="you@example.com"
						required
					>
				</div>

				<div class="affiliate-application-page__field">
					<label for="AffiliateApplicationForm-wp-phone"><?php esc_html_e( 'Phone Number', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
					<input
						type="tel"
						id="AffiliateApplicationForm-wp-phone"
						name="contact[phone]"
						autocomplete="tel"
						placeholder="<?php esc_attr_e( '(555) 123-4567', 'simms-research' ); ?>"
						required
					>
				</div>

				<div class="affiliate-application-page__field">
					<label for="AffiliateApplicationForm-wp-social-handles"><?php esc_html_e( 'Social Handle(s)', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
					<input
						type="text"
						id="AffiliateApplicationForm-wp-social-handles"
						name="contact[Social handles]"
						autocomplete="off"
						placeholder="<?php esc_attr_e( '@yourhandle on Instagram, TikTok, etc.', 'simms-research' ); ?>"
						required
					>
				</div>

				<div class="affiliate-application-page__row">
					<div class="affiliate-application-page__field">
						<label for="AffiliateApplicationForm-wp-instagram-followers"><?php esc_html_e( 'Instagram Followers', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
						<input
							type="number"
							id="AffiliateApplicationForm-wp-instagram-followers"
							name="contact[Instagram followers]"
							inputmode="numeric"
							min="0"
							step="1"
							placeholder="<?php esc_attr_e( 'e.g. 5000', 'simms-research' ); ?>"
							required
						>
					</div>

					<div class="affiliate-application-page__field">
						<label for="AffiliateApplicationForm-wp-tiktok-followers"><?php esc_html_e( 'TikTok Followers', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
						<input
							type="number"
							id="AffiliateApplicationForm-wp-tiktok-followers"
							name="contact[TikTok followers]"
							inputmode="numeric"
							min="0"
							step="1"
							placeholder="<?php esc_attr_e( 'e.g. 12000', 'simms-research' ); ?>"
							required
						>
					</div>
				</div>

				<div class="affiliate-application-page__field">
					<label for="AffiliateApplicationForm-wp-total-followers"><?php esc_html_e( 'Total Followers (All Platforms)', 'simms-research' ); ?></label>
					<select id="AffiliateApplicationForm-wp-total-followers" name="contact[Total followers]">
						<option value="" selected><?php esc_html_e( 'Select a range (optional)...', 'simms-research' ); ?></option>
						<option value="Under 5,000"><?php esc_html_e( 'Under 5,000', 'simms-research' ); ?></option>
						<option value="5,000 - 25,000"><?php esc_html_e( '5,000 - 25,000', 'simms-research' ); ?></option>
						<option value="25,000 - 100,000"><?php esc_html_e( '25,000 - 100,000', 'simms-research' ); ?></option>
						<option value="100,000 - 500,000"><?php esc_html_e( '100,000 - 500,000', 'simms-research' ); ?></option>
						<option value="500,000+"><?php esc_html_e( '500,000+', 'simms-research' ); ?></option>
					</select>
				</div>

				<div class="affiliate-application-page__field">
					<label for="AffiliateApplicationForm-wp-audience-info"><?php esc_html_e( 'Platform / Audience Info', 'simms-research' ); ?></label>
					<textarea
						rows="5"
						id="AffiliateApplicationForm-wp-audience-info"
						name="contact[body]"
						placeholder="<?php esc_attr_e( 'Tell us about your audience, niche, content style, etc.', 'simms-research' ); ?>"
					></textarea>
				</div>

				<div class="affiliate-application-page__field">
					<label for="AffiliateApplicationForm-wp-referral-source"><?php esc_html_e( 'How Did You Find Us?', 'simms-research' ); ?> <span aria-hidden="true">*</span></label>
					<select id="AffiliateApplicationForm-wp-referral-source" name="contact[How did you find us]" required>
						<option value="" selected disabled><?php esc_html_e( 'Select an option...', 'simms-research' ); ?></option>
						<option value="Instagram"><?php esc_html_e( 'Instagram', 'simms-research' ); ?></option>
						<option value="TikTok"><?php esc_html_e( 'TikTok', 'simms-research' ); ?></option>
						<option value="Google search"><?php esc_html_e( 'Google search', 'simms-research' ); ?></option>
						<option value="Existing customer"><?php esc_html_e( 'Existing customer', 'simms-research' ); ?></option>
						<option value="Referred by another partner"><?php esc_html_e( 'Referred by another partner', 'simms-research' ); ?></option>
						<option value="Other"><?php esc_html_e( 'Other', 'simms-research' ); ?></option>
					</select>
				</div>

				<div class="affiliate-application-page__field">
					<label for="AffiliateApplicationForm-wp-notes"><?php esc_html_e( 'Additional Notes', 'simms-research' ); ?></label>
					<textarea
						rows="4"
						id="AffiliateApplicationForm-wp-notes"
						name="contact[Additional notes]"
						placeholder="<?php esc_attr_e( "Anything else you'd like us to know?", 'simms-research' ); ?>"
					></textarea>
				</div>

				<button type="submit" class="button affiliate-application-page__submit">
					<?php esc_html_e( 'Submit Application', 'simms-research' ); ?>
				</button>
			</form>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
