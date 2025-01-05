# Virtual Domain Setup Script

This script helps you set up a WAMP-like environment on a Linux system by allowing you to:
1. Set up virtual domains.
2. Add virtual domains to `/etc/hosts`.
3. Create and configure Apache virtual hosts.
4. Restart or stop Apache and MySQL services.

## Prerequisites

Before using the script, ensure the following are installed:
- **Apache** (`apache2`)
- **MySQL** (`mysql`)
- **sudo** privileges for the user running the script.

## Setup

1. Clone or download this script to your system.
2. Open a terminal and navigate to the folder where the script is stored.
3. Make the script executable:
   ```bash
   chmod +x setup_virtual_domain.sh
