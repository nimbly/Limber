test:
	vendor/bin/phpunit

coverage:
	php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-clover=build/logs/clover.xml

mutation:
	vendor/bin/infection

analyze:
	vendor/bin/psalm