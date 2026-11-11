.PHONY: up down logs health install test
up:
	docker compose up --build -d
down:
	docker compose down
logs:
	docker compose logs --tail=100
health:
	docker compose ps
install:
	docker compose exec --user www-data web sh /usr/local/bin/aula-install
test:
	docker compose config --quiet
	docker compose exec web php -l /var/www/moodle/config.php
	docker compose exec --user www-data web sh -c 'cd /var/www/moodle/public && find theme/et37 local/aulaet37 -name "*.php" -exec php -l {} \; | grep -v "^No syntax errors" || echo "PHP OK"'
