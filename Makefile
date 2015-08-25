.PHONY: test
test:
	cd test/ && ../vendor/bin/phpunit --debug
