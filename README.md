# Simple Symfony Console Command object based queue system.

###### Usage:
1. Add a class implementing the storage interface.
This class will be used to get/add items from/to the queue.
For example in this class you can use a database driver (mysql or alike) to store the commands.
2. Create the symfony console command class(es).
3. Run the symfony console commands by using a queue implementation.
For example [src/Commands/Run.php](src/Commands/Run.php).
But also if you like to create your own implementation this code is a good starting point.
