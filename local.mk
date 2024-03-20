DOCKER=docker

ps:
	@${DOCKER} ps -a
rm all:
	docker rm -f $(docker ps -aq) 