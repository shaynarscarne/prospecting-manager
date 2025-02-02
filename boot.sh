#!/bin/bash
set -e

WP_PATH="/var/www/html"

echo "DB Host: $WORDPRESS_DB_HOST"
echo "DB User: $WORDPRESS_DB_USER"
echo "DB Password: $WORDPRESS_DB_PASSWORD"
echo "DB Name: $WORDPRESS_DB_NAME"

# Download WordPress core files if not present
if [ ! -f ${WP_PATH}/wp-config.php ]; then
  echo "WordPress core files not found. Downloading..."
  wp core download --allow-root --path=${WP_PATH}
  echo "WordPress core files downloaded."

  echo "Creating wp-config.php..."
  wp config create --allow-root --path=${WP_PATH} \
    --dbname="${WORDPRESS_DB_NAME}" \
    --dbuser="${WORDPRESS_DB_USER}" \
    --dbpass="${WORDPRESS_DB_PASSWORD}" \
    --dbhost="${WORDPRESS_DB_HOST}"
  echo "wp-config.php created."
fi

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
until wp db check --allow-root --path=${WP_PATH}; do
  echo "Waiting for database connection..."
  sleep 5
done

# Install WordPress if not already installed
if ! wp core is-installed --allow-root --path=${WP_PATH}; then
  echo "Installing WordPress..."
  wp core install --allow-root --path=${WP_PATH} \
    --url="http://localhost:8000" \
    --title="ProspectingShowcase" \
    --admin_user="admin" \
    --admin_password="admin_password" \
    --admin_email="ProspectingShowcase@example.com"
  echo "WordPress installed successfully."
else
  echo "WordPress is already installed."
fi

# Activate your custom theme
echo "Activating theme 'Prespa'..."
wp theme activate prespa --allow-root --path=${WP_PATH}
echo "Theme 'Prespa' activated."


# Activate your plugin
echo "Activating 'tc-prospecting' plugin..."
wp plugin activate tc-prospecting --allow-root --path=${WP_PATH}
echo "'tc-prospecting' plugin activated."

echo "Setting FS_METHOD to 'direct'..."
wp config set FS_METHOD 'direct' --type=constant --allow-root --path=${WP_PATH}
echo "FS_METHOD set to 'direct'."


echo "Manually creating .htaccess file with WordPress rewrite rules..."
cat << 'EOF' > ${WP_PATH}/.htaccess
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOF

# Set appropriate permissions
chmod 644 ${WP_PATH}/.htaccess
chown www-data:www-data ${WP_PATH}/.htaccess
echo ".htaccess file created."


# Set the permalink structure to "Post name"
echo "Setting permalink structure to 'Post name'..."
wp rewrite structure '/%postname%/' --hard --allow-root --path=${WP_PATH}
wp rewrite flush --hard --allow-root --path=${WP_PATH}
echo "Permalink structure set."

# Create the "Prospecting Database" page if it doesn't exist
if ! wp post list --post_type=page --field=post_title --allow-root --path=${WP_PATH} | grep -q "Prospecting Database"; then
  echo "Creating 'Prospecting Database' page..."
  wp post create --allow-root --path=${WP_PATH} \
    --post_type=page \
    --post_status=publish \
    --post_title="Prospecting Database" \
    --post_content='[main_prospecting]'
  echo "'Prospecting Database' page created successfully."
else
  echo "'Prospecting Database' page already exists."
fi

echo "Flushing rewrite rules via WP function..."
wp eval 'flush_rewrite_rules( true );' --allow-root --path=${WP_PATH}
echo "Rewrite rules flushed."

# Provide the user with a clickable link to access the website.
echo ""
echo "============================================================"
echo "The website is ready! You can access it at:"
echo "http://localhost:8000/prospecting-database/"
echo "============================================================"

exit 0
