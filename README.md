# Simple Command object based queue system.
A simple queue implementation.

Usage:
1. Add a class implementing the storage interface.
This class will be used to get/add items from/to the queue.
For example in this class you can use a database driver (mysql or alike) to store the commands.
2. Create command class(es) implementing the Command interface.
3. Run the commands by using a queue implementation.
If using Symfony Console you can use [src/Commands/Run.php](src/Commands/Run.php).
But also if you like to create your own implementation this code is a good starting point.
