DOCKER=docker

ps:
	@${DOCKER} ps -a
rm all:
	docker rm -f $(docker ps -aq) 
schema validate:
	@${CONSOLE} doctrine:schema:validate
entity:
	@${CONSOLE} make:entity