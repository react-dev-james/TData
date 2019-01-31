
## Ticketdata

  

### Build project

	- composer install

	- npm install

	- cp .env.example .env

	- php artisan key:generate
   #### Install DB
	   -  sudo chown -R _mysql:mysql /usr/local/var/mysql   (DB Select)
	   -  sudo mysql.server start                           ( DB Start)
	   -  php artisan cache:clear
	   -  php artisan config:cache
	   -  php artisan migrate                               (DB Migarate)
	   -  php artisan db:seed                               (DB insert)
	   -  php artisan serve                                 ( Start Server)

#### Error: InvalidArgumentException Please provide a valid cache path.
	**storage/framework**

	**sessions**

	**views**

	**cache**