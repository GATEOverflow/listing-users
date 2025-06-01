Q2A Plugin: User Role Management & Notifications

This plugin for Question2Answer allows administrators to:

View and manage users by their role.

Change a user's role dynamically.

Define default notice messages for each user level.

Notify users of role changes via private notices.



⚙ Admin Configuration Options
Minimum Level to Access "List Users" Page: Choose the user level required to access the user listing and role management panel.

Custom Notice Messages: Define a default message per user level. These are shown in notifications if no custom message is entered during role change.

📝 Usage
Go to /list-users (or use the link from your plugin list).

Select a role to filter users.

Change a user's role using the dropdown.

Submit the form to update the role and send the user a formatted private notice.


🔐 Permissions & Restrictions
Only users with the configured minimum level can access the interface.

Role change is only allowed if:

The current user has higher privileges.

The new role is not equal or higher than the current user's level (unless super admin).


📂 File Overview
File				Description
list-users.php	Main plugin entry point
list_users.php	Core logic: admin panel + user table
qa-plugin.php	Plugin metadata


🛠 Installation
Copy the plugin files into the qa-plugin directory of your Q2A installation.

Log in to Q2A as a site administrator.

Navigate to Admin → Plugins and configure the plugin settings.