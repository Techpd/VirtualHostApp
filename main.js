#!/usr/bin/env gjs

imports.gi.versions.Gtk = '4.0'; // Or '3.0', depending on your system
const { Gtk, GLib, Gio, Gdk } = imports.gi;
Gtk.init(); // Initialize Gtk
const GioUnix = imports.gi.GioUnix;


class WAMPPManager {
    constructor() {
        this.application = new Gtk.Application({
            application_id: 'com.example.wamppmanager',
            flags: Gio.ApplicationFlags.FLAGS_NONE,
        });

        this.application.connect('activate', this.onActivate.bind(this));
        this.logBuffer = new Gtk.TextBuffer();

        // Load CSS
        this.loadCss();
    }

    // Function to load the CSS file
    loadCss() {
        const provider = new Gtk.CssProvider();
        const cssFilePath = GLib.build_filenamev([GLib.get_current_dir(), 'style.css']); // Dynamically resolve the CSS path

        try {
            provider.load_from_path(cssFilePath);
            Gtk.StyleContext.add_provider_for_display(
                Gdk.Display.get_default(), // Get the default display
                provider,
                Gtk.STYLE_PROVIDER_PRIORITY_APPLICATION
            );
            print(`Successfully loaded CSS file: ${cssFilePath}`);
        } catch (e) {
            print(`Failed to load CSS file: ${cssFilePath}\nError: ${e.message}`);
        }
    }

    onActivate() {
        const window = new Gtk.ApplicationWindow({
            application: this.application,
            title: 'WAMPP Manager',
            default_width: 900,
            default_height: 650,
        });

        const mainBox = new Gtk.Box({
            orientation: Gtk.Orientation.VERTICAL,
            spacing: 10,
            margin_top: 20,
            margin_bottom: 20,
            margin_start: 20,
            margin_end: 20,
        });

        // Title
        const header = new Gtk.Label({
            label: '<span size="18000" weight="bold" color="#0078D7">WAMPP Manager</span>',
            use_markup: true,
            halign: Gtk.Align.CENTER,
        });

        // Buttons for actions
        const buttonBox = new Gtk.Box({
            orientation: Gtk.Orientation.HORIZONTAL,
            spacing: 20,
        });

        const installButton = new Gtk.Button({ label: 'Install LAMP & phpMyAdmin' });
        installButton.connect('clicked', () => {
            this.runCommand(
                'sudo apt update && sudo apt install -y apache2 mysql-server php libapache2-mod-php php-mysql phpmyadmin'
            );
        });

        const startButton = new Gtk.Button({ label: 'Start Services' });
        startButton.connect('clicked', () => {
            this.runCommand('sudo systemctl start apache2 mysql');
        });

        const stopButton = new Gtk.Button({ label: 'Stop Services' });
        stopButton.connect('clicked', () => {
            this.runCommand('sudo systemctl stop apache2 mysql');
        });

        const statusButton = new Gtk.Button({ label: 'Service Status' });
        statusButton.connect('clicked', () => {
            this.runCommand('systemctl status apache2 mysql');
        });

        const hostButton = new Gtk.Button({ label: 'Setup Virtual Host' });
        hostButton.connect('clicked', () => {
            this.setupVirtualHost();
        });

        buttonBox.append(installButton);
        buttonBox.append(startButton);
        buttonBox.append(stopButton);
        buttonBox.append(statusButton);
        buttonBox.append(hostButton);

        // Real-time log display
        const logView = new Gtk.TextView({ editable: false, wrap_mode: Gtk.WrapMode.WORD });
        logView.set_buffer(this.logBuffer);

        const logScroll = new Gtk.ScrolledWindow();
        logScroll.set_policy(Gtk.PolicyType.AUTOMATIC, Gtk.PolicyType.AUTOMATIC);
        logScroll.set_child(logView);

        // Add components to the main box
        mainBox.append(header);
        mainBox.append(buttonBox);
        mainBox.append(logScroll);
        window.set_child(mainBox);

        window.show();
    }

    runCommand(command) {
        const [success, argv] = GLib.shell_parse_argv(command);
        if (!success) return;

        // Specify the full path to the commands
        const echoCmd = '/bin/echo';  // Use the correct path for echo
        const sudoCmd = '/usr/bin/sudo';  // Use the correct path for sudo
        const systemctlCmd = '/bin/systemctl';  // Use the correct path for systemctl

        // Replace the commands in the input string with their absolute paths
        command = command.replace(/echo/g, echoCmd).replace(/sudo/g, sudoCmd).replace(/systemctl/g, systemctlCmd);

        const [ok, pid, stdin, stdout, stderr] = GLib.spawn_async_with_pipes(
            null,
            GLib.shell_parse_argv(command)[1],
            null,
            GLib.SpawnFlags.DO_NOT_REAP_CHILD,
            null
        );

        if (ok) {
            // Create input streams using the updated class
            const outputStream = new Gio.DataInputStream({ base_stream: new GioUnix.InputStream({ fd: stdout }) });
            const errorStream = new Gio.DataInputStream({ base_stream: new GioUnix.InputStream({ fd: stderr }) });
        
            this.readStream(outputStream, 'stdout');
            this.readStream(errorStream, 'stderr');
        }
        
    }

    uint8ArrayToString(array) {
        let string = '';
        for (let i = 0; i < array.length; i++) {
            string += String.fromCharCode(array[i]);
        }
        return string;
    }

    readStream(stream, streamType) {
        stream.read_line_async(0, null, (src, res) => {
            const [line] = src.read_line_finish(res);

            if (line) {
                GLib.idle_add(GLib.PRIORITY_DEFAULT, () => {
                    // Corrected the insert method with the proper arguments
                    const decodedLine = this.uint8ArrayToString(line);
                    this.logBuffer.insert(this.logBuffer.get_end_iter(), `[${streamType}] ${decodedLine}\n`, -1);
                    return GLib.SOURCE_CONTINUE;
                });
                this.readStream(stream, streamType);
            }
        });
    }


    setupVirtualHost() {
        const dialog = new Gtk.Dialog({
            title: 'Setup Virtual Host',
            transient_for: this.application.active_window,
            modal: true,
        });

        dialog.add_button('Cancel', Gtk.ResponseType.CANCEL);
        dialog.add_button('Setup', Gtk.ResponseType.OK);

        const contentBox = dialog.get_content_area();
        const grid = new Gtk.Grid({
            column_spacing: 10,
            row_spacing: 10,
            margin_top: 20,
            margin_bottom: 20,
            margin_start: 20,
            margin_end: 20,
        });

        const projectLabel = new Gtk.Label({ label: 'Project Path:' });
        const projectEntry = new Gtk.Entry();
        projectEntry.set_placeholder_text('/var/www/html/project-name');

        const hostLabel = new Gtk.Label({ label: 'Virtual Host Name:' });
        const hostEntry = new Gtk.Entry();
        hostEntry.set_placeholder_text('example.local');

        grid.attach(projectLabel, 0, 0, 1, 1);
        grid.attach(projectEntry, 1, 0, 1, 1);
        grid.attach(hostLabel, 0, 1, 1, 1);
        grid.attach(hostEntry, 1, 1, 1, 1);

        contentBox.append(grid);
        dialog.show();

        dialog.connect('response', (dlg, response) => {
            if (response === Gtk.ResponseType.OK) {
                const projectPath = projectEntry.text;
                const hostName = hostEntry.text;

                if (projectPath && hostName) {
                    const command = `
                        echo '<VirtualHost *:80>
                            ServerName ${hostName}
                            DocumentRoot ${projectPath}
                            <Directory ${projectPath}>
                                AllowOverride All
                                Require all granted
                            </Directory>
                        </VirtualHost>' | sudo tee /etc/apache2/sites-available/${hostName}.conf &&
                        sudo a2ensite ${hostName}.conf &&
                        sudo systemctl reload apache2
                    `;
                    this.runCommand(command);
                }
            }
            dlg.destroy();
        });
    }

}

const wamppManager = new WAMPPManager();
wamppManager.application.run([]);  // Pass an empty array as argument
