DOCKER=docker

ps:
	@${DOCKER} ps -a
	
rm all:
	docker rm -f $(docker ps -aq) 
	
schema validate:
	@${CONSOLE} doctrine:schema:validate
	
entity:
	@${CONSOLE} make:entity
	
exec php: 
	@$(COMPOSE) exec -it php bash

encore_dev:
	@${COMPOSE} run node yarn encore dev --watch

encore_prod:
	@${COMPOSE} run node yarn encore production