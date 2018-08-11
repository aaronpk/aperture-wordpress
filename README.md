# WordPress Aperture Plugin

This plugin adds a [Microsub](https://indieweb.org/Microsub) endpoint to your WordPress site by using the hosted [Aperture](https://aperture.p3k.io) service. This lets you log in to social readers like [Monocle](https://monocle.p3k.io) and [Indigenous](https://indigenous.abode.pub/ios/).

Available in the WordPress plugin directory at [wordpress.org/plugins/aperture](https://wordpress.org/plugins/aperture/).

> This plugin requires the [WordPress IndieAuth plugin](https://wordpress.org/plugins/indieauth/). Please ensure that plugin is installed and activated first before attempting to install the Aperture plugin.

When this plugin is activated, it registers a new account at [Aperture](https://aperture.p3k.io). The `<link rel="microsub">` tag is then added to your WordPress site automatically.

![activated](assets/aperture-account-activated.png)

After activating this plugin, you should be able to log in to Aperture and Microsub clients. Note that no data is stored in WordPress, this plugin just sets the appropriate tags so that you can use Aperture as your Microsub server.
