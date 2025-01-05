#!/bin/bash

# Function to prompt user for input
prompt() {
    echo "$1"
    read -p "Enter choice (Y/n): " choice
    if [[ -z "$choice" || "$choice" == "Y" || "$choice" == "y" ]]; then
        return 0
    else
        return 1
    fi
}

# Function to restart the services
restart_services() {
    sudo systemctl restart apache2
    sudo systemctl restart mysql
    echo "Services restarted!"
}

# Function to stop services
stop_services() {
    sudo systemctl stop apache2
    sudo systemctl stop mysql
    echo "Services stopped!"
}

# Function to check if the virtual domain is already set up
check_virtual_domain_exists() {
    local domain_name="$1"
    # Check if the virtual host configuration already exists
    if [ -f "/etc/apache2/sites-available/$domain_name.conf" ]; then
        echo "The virtual domain $domain_name already exists."
        return 0
    else
        return 1
    fi
}

# Function to setup virtual domain
setup_virtual_domain() {
    echo "Setting up virtual domain..."

    # Ask for the domain name
    read -p "Enter the domain name (e.g., mysite.local): " domain_name

    # Check if the domain already exists in /etc/hosts
    if grep -q "$domain_name" /etc/hosts; then
        echo "The domain $domain_name is already present in /etc/hosts."
    else
        # Check if the comment section for virtual domains is already present
        if ! grep -q "#virtual domains" /etc/hosts; then
            # Add the domain name to /etc/hosts with comments and spacing, only once
            echo -e "\n#virtual domains\n#BY Techpd" | sudo tee -a /etc/hosts > /dev/null
            echo "Comment section for virtual domains added to /etc/hosts."
        fi
        # Add the domain to /etc/hosts
        echo "127.0.0.1 $domain_name" | sudo tee -a /etc/hosts > /dev/null
        echo "Domain $domain_name added to /etc/hosts."
    fi

    # Check if the domain is already set up as a virtual host
    check_virtual_domain_exists "$domain_name"
    if [ $? -eq 0 ]; then
        echo "Skipping virtual host setup for $domain_name."
        return
    fi

    # Ask for the document root path
    read -p "Enter the document root path (e.g., /var/www/html/mysite): " doc_root

    # Create the directory for the document root
    sudo mkdir -p "$doc_root"
    
    # Create a simple index.php file in the document root
    echo "<?php phpinfo(); ?>" | sudo tee "$doc_root/index.php" > /dev/null

    # Create a virtual host configuration file for the domain
    sudo bash -c "cat > /etc/apache2/sites-available/$domain_name.conf <<EOF
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName $domain_name
    DocumentRoot $doc_root

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF"

    # Enable the site and reload Apache
    sudo a2ensite "$domain_name.conf"
    sudo systemctl reload apache2

    echo "Virtual domain $domain_name setup completed!"
}

# Prompt the user for desired actions

if prompt "Do you want to setup a virtual domain?"; then
    setup_virtual_domain
fi

if prompt "Do you want to restart Apache and MySQL services?"; then
    restart_services
fi

if prompt "Do you want to stop Apache and MySQL services?"; then
    stop_services
fi

echo "WAMP-like environment setup complete!"
