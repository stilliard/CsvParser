
unit-test:
	./vendor/bin/phpunit .

code-coverage:
	./vendor/bin/phpunit --coverage-html tmp/code-coverage-report test
	php -S localhost:8977 -t tmp/code-coverage-report
