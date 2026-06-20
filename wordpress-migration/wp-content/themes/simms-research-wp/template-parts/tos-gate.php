<?php
/**
 * Research-use terms gate dialog.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section
	id="tos-gate"
	class="tos-gate tos-gate--has-image"
	role="dialog"
	aria-modal="true"
	aria-labelledby="tos-gate-heading"
	hidden
>
	<div class="tos-gate__inner">
		<header class="tos-gate__brand">
			<img
				class="tos-gate__logo"
				src="<?php echo esc_url( SIMMS_THEME_URI . '/assets/images/simms-logo.png' ); ?>"
				alt="<?php esc_attr_e( 'Simms Research', 'simms-research' ); ?>"
				width="800"
				height="200"
			>
		</header>

		<div class="tos-gate__card">
			<span class="tos-gate__pill"><?php esc_html_e( 'Research access', 'simms-research' ); ?></span>

			<h2 id="tos-gate-heading" class="tos-gate__heading"><?php esc_html_e( 'View our catalog', 'simms-research' ); ?></h2>

			<p class="tos-gate__body">
				<?php
				printf(
					wp_kses(
						/* translators: %s: linked terms label. */
						__( 'By entering, you accept our %s and confirm you are of legal age to view research compounds in your jurisdiction.', 'simms-research' ),
						array(
							'a' => array(
								'class' => true,
								'href'  => true,
							),
						)
					),
					'<a href="' . esc_url( home_url( '/terms-conditions/' ) ) . '" class="tos-gate__link">' . esc_html__( 'Terms', 'simms-research' ) . '</a>'
				);
				?>
			</p>

			<ul class="tos-gate__trust" role="list">
				<li>
					<span class="tos-gate__trust-value"><?php esc_html_e( '99%+', 'simms-research' ); ?></span>
					<span class="tos-gate__trust-label"><?php esc_html_e( 'Purity', 'simms-research' ); ?></span>
				</li>
				<li>
					<span class="tos-gate__trust-value"><?php esc_html_e( 'Lab', 'simms-research' ); ?></span>
					<span class="tos-gate__trust-label"><?php esc_html_e( 'Certified', 'simms-research' ); ?></span>
				</li>
				<li>
					<span class="tos-gate__trust-value"><?php esc_html_e( 'Research', 'simms-research' ); ?></span>
					<span class="tos-gate__trust-label"><?php esc_html_e( 'Use only', 'simms-research' ); ?></span>
				</li>
			</ul>

			<p class="tos-gate__fine-print">
				<?php esc_html_e( 'All compounds are sold for research and laboratory use. Products are not for human consumption or medical use.', 'simms-research' ); ?>
			</p>

			<label class="tos-gate__remember">
				<input type="checkbox" id="tos-gate-remember">
				<span><?php esc_html_e( 'Remember for 30 days', 'simms-research' ); ?></span>
			</label>

			<div class="tos-gate__actions">
				<button type="button" class="tos-gate__btn tos-gate__btn--secondary" id="tos-gate-decline">
					<?php esc_html_e( 'Leave site', 'simms-research' ); ?>
				</button>
				<button type="button" class="tos-gate__btn tos-gate__btn--primary" id="tos-gate-accept">
					<?php esc_html_e( 'Enter site', 'simms-research' ); ?>
				</button>
			</div>
		</div>

		<footer class="tos-gate__footer">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'Simms', 'simms-research' ); ?> &middot; <?php esc_html_e( 'All rights reserved', 'simms-research' ); ?>
		</footer>
	</div>
</section>
