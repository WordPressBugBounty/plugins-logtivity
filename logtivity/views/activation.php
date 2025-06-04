<div style="<?php echo ($display ?? false) ? 'display:block;' : '' ?>"
     class="updated notice logtivity-notice is-dismissible">
    <div>
        <div style="<?php echo ($logo ?? false) ? '' : 'display:none;'; ?>">
            <img style="margin: 0 0 20px; display: block; width: 200px; height: auto;"
                 src="<?php echo sprintf('%s/logtivity/assets/logtivity-logo.svg', plugin_dir_url('logtivity.php')) ?>"
                 alt="Logtivity">
        </div>
        <div>
            <h3>
                <?php esc_html_e('Welcome to Logtivity! We make activity logs simple!', 'logtivity') ?>
            </h3>
            <p>
                <?php esc_html_e("Here's how to start your activity log for this site:", 'logtivity') ?>
            </p>
            <ol>
                <li>
                    <?php
                    echo sprintf(
                        esc_html__('%1$sCreate%2$s or %3$slogin%4$s to your Logtivity account.', 'logtivity'),
                        '<a target="_blank" href="' . logtivity_get_app_url() . '/register">',
                        '</a>',
                        '<a target="_blank" href="' . logtivity_get_app_url() . '/login">',
                        '</a>'
                    );
                    ?>
                </li>
                <li>
                    <?php
                    echo sprintf(
                        esc_html__('%sClick the "Add Site" button%s  and get your API Key.', 'logtivity'),
                        '<a target="_blank" href="https://logtivity.io/docs/connect-your-site-to-logtivity/">',
                        '</a>'
                    );
                    ?>
                </li>
                <li>
                    <?php
                    echo sprintf(
                        esc_html__('Add your API Key into %sthe "Settings" area%s on this site.', 'logtivity'),
                        '<a target="_blank" href="' . admin_url('admin.php?page=logtivity-settings') . '">',
                        '</a>'
                    );
                    ?>
                </li>
            </ol>
            <p>
                <a class="button-primary logtivity-button logtivity-button-secondary"
                   target="_blank"
                   href="<?php echo logtivity_get_app_url() . '/login'; ?>">
                    <?php esc_html_e('Login to Logtivity', 'logtivity'); ?>
                </a> <a class="button-primary logtivity-button logtivity-button-primary"
                        target="_blank"
                        href="<?php echo logtivity_get_app_url() . '/register'; ?>">
                    <?php esc_html_e('Start your free 10 day trial', 'logtivity'); ?>
                </a>
            </p>
        </div>
    </div>
</div>
