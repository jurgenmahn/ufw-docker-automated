# ufw-docker-automated
Automatically update ufw rules for docker containers


Based on the idea of https://github.com/shinebayar-g/ufw-docker-automated and https://github.com/chaifeng/ufw-docker

I prefered a bit simpler approach and more control over the access rules to the docker containers, so instead of usings the dockers labels the specify the firewall rules as shinebayar-g does in his solution i decided to use a separate config file which is reloaded on the fly to specify the firewall rules. This because I want to have the posibility to update the rules without recreating the containers.

I used PHP since im not a python developer.

The base ufw rules are taken from chaifeng, thanks for that, saves me a lot of time figuring this out. and the idea to automate is is taken from shinebayar-g.

Installation manual:

.....


Current status.

Just starting
