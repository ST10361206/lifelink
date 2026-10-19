LIFELINK WEBSITE - STARTER PROJECT

1. Install XAMPP with Apache, PHP and MySQL.
2. Start Apache and MySQL in XAMPP Control Panel.
3. Extract the lifelink folder into C:\xampp\htdocs\lifelink.
4. Open http://localhost/phpmyadmin in your browser.
5. Select Import, choose C:\xampp\htdocs\lifelink\database.sql, and click Import/Go.
6. Open Visual Studio 2022 > File > Open > Folder and select C:\xampp\htdocs\lifelink.
7. Open http://localhost/lifelink/ in a browser. PHP pages must be opened via localhost, not by double-clicking files.
8. To test booking, first add a real or TEST centre to the centres table using phpMyAdmin > lifelink > centres > Insert. For example: name = Test Donation Centre, address = Test location (not a real centre).
9. Register a donor, log in, and submit an appointment request. It will appear as Pending.

NOT YET IMPLEMENTED: admin portal, donor eligibility screening, email alerts, blood inventory, profile/history, REST API, appointment capacity checks, and live maps. This is a learning prototype, not a production medical system.

If MySQL has a password, update includes/db.php to match.
