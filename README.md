**Q2A Plugin: User Role Management**

This plugin for Question2Answer allows administrators to:
<ul>
  <li>View and manage users by their role.</li>
  <li>Change a user's role dynamically.</li>
  <li>Define default notice messages for each user level.</li>
  <li>Notify users of role changes via private notices.</li>
</ul>


⚙ Admin Configuration Options <br />
**Minimum Level to Access "List Users" Page:** Choose the user level required to access the user listing and role management panel.

**Custom Notice Messages:** Define a default message per user level. These are shown in notifications if no custom message is entered during role change.

📝 Usage <br />
<ul>
<li>Go to /list-users (or use the link from your plugin list).</li>
<li>Select a role to filter users.</li>
<li>Change a user's role using the dropdown.</li>
<li>Submit the form to update the role and send the user a formatted private notice.</li>
  </ul>


🔐 Permissions & Restrictions <br />
<ul>
<li>Only users with the configured minimum level can access the interface.</li>
<li>
  <ul>Role change is only allowed if:
    <li>The current user has higher privileges.</li>
  <li>The new role is not equal or higher than the current user's level (unless super admin).</li>
</ul>
</li>
  </ul>

📂 File Overview <br />
<ul>
<li>list-users.php -	Main plugin entry point, Core logic: admin panel + user table</li>
<li>qa-plugin.php	- Plugin metadata</li>
  </ul>


🛠 Installation <br />
<ul>
<li>Copy the plugin files into the qa-plugin directory of your Q2A installation.</li>
<li>Log in to Q2A as a site administrator.</li>
<li>Navigate to Admin → Plugins and configure the plugin settings.</li>
  </ul>
