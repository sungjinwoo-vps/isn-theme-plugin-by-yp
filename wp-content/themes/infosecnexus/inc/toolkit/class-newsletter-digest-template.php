<?php
/**
 * Daily Cyber Brief email layout.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

final class Newsletter_Digest_Template {
	/**
	 * Render a digest using inline styles and tables for mail clients.
	 *
	 * @param string                                  $date Issue date.
	 * @param array<int, array{title:string, url:string}> $entries Published articles.
	 * @param string                                  $brief_url Permanent Live Brief URL.
	 * @param string                                  $unsubscribe Recipient's unsubscribe URL.
	 */
	public static function render( string $date, array $entries, string $brief_url, string $unsubscribe ): string {
		$logo_url  = get_template_directory_uri() . '/assets/brand/logo.png';
		$brief_url = esc_url_raw( $brief_url );

		ob_start();
		?>
		<div style="margin:0;background-color:#f5f0e9;color:#171922;font-family:Arial,Helvetica,sans-serif;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f0e9" style="width:100%;background-color:#f5f0e9;">
				<tr><td align="center" style="padding:24px 12px;">
					<!--[if mso]><table role="presentation" width="620" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
					<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;">
						<tr><td bgcolor="#fffaf4" style="padding:28px 24px;background-color:#fffaf4;border:1px solid #e7dacd;border-top:4px solid #fa622d;border-radius:22px;">
							<img src="<?php echo esc_url( $logo_url ); ?>" width="170" height="29" alt="InfoSecNexus" style="display:block;max-width:100%;height:auto;color:#171922;font-size:16px;font-weight:bold;">
							<h1 style="margin:22px 0 6px;color:#171922;font-size:32px;line-height:1.15;letter-spacing:-0.8px;"><?php echo esc_html( __( 'Daily Cyber Brief', 'infosecnexus' ) ); ?></h1>
							<p style="margin:0;color:#a93816;font-size:14px;line-height:1.5;font-weight:bold;"><?php echo esc_html( $date ); ?></p>
						</td></tr>
						<tr><td style="padding:22px 4px 10px;">
							<h2 style="margin:0;color:#171922;font-size:21px;line-height:1.3;"><?php echo esc_html( __( 'Latest intelligence', 'infosecnexus' ) ); ?></h2>
						</td></tr>
						<?php foreach ( $entries as $entry ) : ?>
							<?php
							$url   = esc_url_raw( $entry['url'] );
							$title = wp_strip_all_tags( wp_specialchars_decode( $entry['title'], ENT_QUOTES ), true );
							if ( '' === $url || '' === $title ) {
								continue;
							}
							?>
							<tr><td style="padding:0 0 10px;">
								<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;">
									<tr><td bgcolor="#fbf8f3" style="padding:16px 18px;background-color:#fbf8f3;border:1px solid #e7dacd;border-left:3px solid #fa622d;border-radius:14px;">
										<a href="<?php echo esc_url( $url ); ?>" style="display:block;color:#a93816;font-size:16px;line-height:1.5;font-weight:bold;text-decoration:underline;overflow-wrap:anywhere;"><?php echo esc_html( $title ); ?></a>
									</td></tr>
								</table>
							</td></tr>
						<?php endforeach; ?>
						<?php if ( '' !== $brief_url ) : ?>
							<tr><td align="center" style="padding:14px 0 24px;">
								<table role="presentation" cellpadding="0" cellspacing="0" border="0">
									<tr><td align="center" bgcolor="#bd4216" style="background-color:#bd4216;border-radius:24px;mso-padding-alt:13px 23px;">
										<a href="<?php echo esc_url( $brief_url ); ?>" style="display:inline-block;padding:13px 23px;color:#ffffff;font-size:15px;line-height:1.4;font-weight:bold;text-decoration:none;"><?php echo esc_html( __( 'Read the live brief', 'infosecnexus' ) ); ?></a>
									</td></tr>
								</table>
							</td></tr>
						<?php endif; ?>
						<tr><td align="center" style="padding:18px 10px 4px;border-top:1px solid #e7dacd;color:#67615c;font-size:13px;line-height:1.6;">
							<p style="margin:0 0 8px;"><?php echo esc_html( __( 'You subscribed to the InfoSecNexus Daily Cyber Brief.', 'infosecnexus' ) ); ?></p>
							<a href="<?php echo esc_url( $unsubscribe ); ?>" style="color:#a93816;font-weight:bold;text-decoration:underline;"><?php echo esc_html( __( 'Unsubscribe', 'infosecnexus' ) ); ?></a>
						</td></tr>
					</table>
					<!--[if mso]></td></tr></table><![endif]-->
				</td></tr>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
