<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyLAMP Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f4f4f9;
        }

        h1 {
            color: #333;
        }

        .button {
            background-color: #4CAF50;
            color: white;
            padding: 15px 32px;
            margin: 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #45a049;
        }


        .message {
            margin-block: 10px 20px;
            font-size: 16px;
            padding: 10px;
            margin-inline: 10px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 5px;
        }

        #virtual-domain-wrapper {
            max-width: 500px;
            margin: 10px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        #virtual-domain-wrapper div {
            margin-bottom: 15px;
        }

        #virtual-domain-wrapper label {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            display: inline-block;
            color: #333;
        }

        #virtual-domain-wrapper input[type="text"] {
            width: 100%;
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        #virtual-domain-wrapper input[type="text"]:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        #virtual-domain-wrapper .button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            margin: 0;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #virtual-domain-wrapper .button:hover {
            background-color: #0056b3;
        }


        /* Fade-in effect */
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <h1>MyLAMP Manager</h1>

    <button class="button" id="setupVHostButton">Setup Virtual Domain</button>
    <button class="button" id="restartButton">Restart Apache and MySQL</button>
    <!-- <button class="button" id="stopButton">Stop Apache and MySQL</button> -->
    <button class="button" id="checkStatusButton">Check Service Status</button>

    <div id="loadingMessage" style="display: none;">
        <p>Restarting services, please wait...</p> <!-- You can add a spinner here if needed -->
    </div>
    <div class="message" id="message" style="display: none;"></div>

    <div id="virtual-domain-wrapper" style="display: none;">
        <div>
            <label for="virtual-domain-name">Enter the domain name (e.g., mysite.local)</label><br>
            <input id="virtual-domain-name" type="text" class="text">
        </div><br>
        <div>
            <label for="virtual-domain-path">Enter the document root path (e.g., /var/www/html/mysite)</label><br>
            <input value="/var/www/html/" id="virtual-domain-path" type="text" class="text">
        </div>
        <div>
            <button class="button" id="addVHostButton">Add Virtual Domain</button>
        </div>
    </div>

    <script>
        document.getElementById('setupVHostButton').addEventListener('click', function() {
            const virtualDomainWrapperElement = document.getElementById('virtual-domain-wrapper');
            // Remove the fade-in class to reset the animation
            virtualDomainWrapperElement.classList.remove('fade-in');
            // Update the message content
            virtualDomainWrapperElement.style.display = 'block';
            // Trigger the fade-in effect by re-adding the class
            void virtualDomainWrapperElement.offsetWidth; // This forces a reflow to restart the animation
            virtualDomainWrapperElement.classList.add('fade-in');
        });

        document.getElementById('addVHostButton').addEventListener('click', function() {
            const domain = document.getElementById('virtual-domain-name').value;
            const docRoot = document.getElementById('virtual-domain-path').value;

            if (domain && docRoot) {
                setupVirtualHost(domain, docRoot);
            } else {
                showMessage('Error', 'Domain name or document root not specified.');
            }
        });

        document.getElementById('restartButton').addEventListener('click', function() {
            showLoadingState(); // Show loading state while waiting for the restart
            ajaxRequest('restart_services', null, function(response) {
                hideLoadingState(); // Hide loading state once the services are restarted
                if (response.success) {
                    showMessage('Services Restarted', response.message);
                } else {
                    showMessage('Error', response.message);
                }
            });
        });

        // document.getElementById('stopButton').addEventListener('click', function() {
        //     ajaxRequest('stop_services', null, function(response) {
        //         showMessage('Services Stopped', 'Apache and MySQL have been stopped.');
        //     });
        // });

        document.getElementById('checkStatusButton').addEventListener('click', function() {
            ajaxRequest('check_services_status', null, function(response) {
                // console.log(response);

                if (response) {
                    showMessage('Service Status', `${response.apache_status}<br> ${response.mysql_status}`);
                } else {
                    showMessage('Error', 'Failed to fetch service status.');
                }
            });
        });

        function ajaxRequest(action, data, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'server.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    callback(response);
                } else {
                    showMessage('Error', 'Failed to execute the action.');
                }
            };
            xhr.send(JSON.stringify({
                action: action,
                data: data
            }));
        }

        function setupVirtualHost(domain, docRoot) {
            const data = {
                action: 'setup_virtual_domain',
                domain: domain,
                doc_root: docRoot
            };

            ajaxRequest('setup_virtual_domain', data, function(response) {
                if (response.success) {
                    showMessage('Virtual Domain Setup', `Domain ${domain} has been successfully set up.`);
                } else {
                    showMessage('Error', response.message || 'Something went wrong!');
                }
            });
        }

        function ajaxRequest(action, data, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'server.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    callback(response);
                } else {
                    showMessage('Error', 'Failed to execute the action.');
                }
            };
            xhr.send(JSON.stringify({
                action: action,
                data: data
            }));
        }

        function showLoadingState() {
            // You can display a loading spinner or a message to inform the user that the action is being processed.
            document.getElementById('loadingMessage').style.display = 'block'; // Example: Show a loading message
        }

        function hideLoadingState() {
            // Hide the loading state after the process is complete
            document.getElementById('loadingMessage').style.display = 'none';
        }

        function showMessage(title, message) {
            const messageElement = document.getElementById('message');
            // Remove the fade-in class to reset the animation
            messageElement.classList.remove('fade-in');
            // Update the message content
            messageElement.innerHTML = `${title}: ${message}`;
            messageElement.style.display = 'block';
            // Trigger the fade-in effect by re-adding the class
            void messageElement.offsetWidth; // This forces a reflow to restart the animation
            messageElement.classList.add('fade-in');
        }
    </script>
</body>

</html>