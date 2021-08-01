# ufw-docker-automated
Automatically update ufw rules for docker containers


Based on the idea of https://github.com/shinebayar-g/ufw-docker-automated and https://github.com/chaifeng/ufw-docker

I prefered a bit simpler approach and more control over the access rules to the docker containers, so instead of usings the dockers labels the specify the firewall rules as shinebayar-g does in his solution i decided to use a separate config file which is reloaded on the fly to specify the firewall rules. This because I want to have the posibility to update the rules without recreating the containers.

I used PHP since im not a python developer.

The base ufw rules are taken from chaifeng, thanks for that, saves me a lot of time figuring this out. The idea to automate it, is taken from shinebayar-g.

Installation manual:

Add the following lines to the bottom your /etc/ufw/after.rules 

```
# BEGIN UFW AND DOCKER
*filter
:ufw-user-forward - [0:0]
:ufw-docker-logging-deny - [0:0]
:DOCKER-USER - [0:0]
-A DOCKER-USER -j ufw-user-forward

-A DOCKER-USER -j RETURN -s 10.0.0.0/8
-A DOCKER-USER -j RETURN -s 172.16.0.0/12
-A DOCKER-USER -j RETURN -s 192.168.0.0/16

-A DOCKER-USER -p udp -m udp --sport 53 --dport 1024:65535 -j RETURN

-A DOCKER-USER -j ufw-docker-logging-deny -p tcp -m tcp --tcp-flags FIN,SYN,RST,ACK SYN -d 192.168.0.0/16
-A DOCKER-USER -j ufw-docker-logging-deny -p tcp -m tcp --tcp-flags FIN,SYN,RST,ACK SYN -d 10.0.0.0/8
-A DOCKER-USER -j ufw-docker-logging-deny -p tcp -m tcp --tcp-flags FIN,SYN,RST,ACK SYN -d 172.16.0.0/12
-A DOCKER-USER -j ufw-docker-logging-deny -p udp -m udp --dport 0:32767 -d 192.168.0.0/16
-A DOCKER-USER -j ufw-docker-logging-deny -p udp -m udp --dport 0:32767 -d 10.0.0.0/8
-A DOCKER-USER -j ufw-docker-logging-deny -p udp -m udp --dport 0:32767 -d 172.16.0.0/12

-A DOCKER-USER -j RETURN

-A ufw-docker-logging-deny -m limit --limit 3/min --limit-burst 10 -j LOG --log-prefix "[UFW DOCKER BLOCK] "
-A ufw-docker-logging-deny -j DROP

COMMIT
# END UFW AND DOCKER
```

Run the phar file, to make sure it keeps running add it to systemd, supervisor or cron. Some examples will be added later

nohup php ufw-docker.phar &


Current status.

Just starting
