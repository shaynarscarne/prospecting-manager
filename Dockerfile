FROM wordpress:latest

# Copy plugin into the WordPress plugins directory
COPY ./tc-prospecting /var/www/html/wp-content/plugins/tc-prospecting
# Copy theme into the WordPress themes directory
COPY ./prespa /var/www/html/wp-content/themes/prespa

# Install MySQL client and netcat (for network connectivity checks)
RUN apt-get update && \
    apt-get install -y default-mysql-client netcat-openbsd && \
    rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x /usr/local/bin/wp


# Copy the boot script into the container and make it executable
COPY boot.sh /usr/local/bin/boot.sh
RUN chmod +x /usr/local/bin/boot.sh

