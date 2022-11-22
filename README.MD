
# Wordpress - Docker - Postgres Microservice

## Dependencies
#### Python, Docker, PostgreSQL, wordpress

* We will use NGINX as a load balancer, the routing criteria will be guaranteed by the round-robin weight parameter :

```
nginx/nginx.conf
```

* Some important points regarding docker-compose.yml:

It will build images for site1, site2, Nginx based on our Dockerfiles and then spin up containers from those images.
The opened port inside site1 and site2 containers are 8000 (default port openend by compose), these ports will be mapped to 8000 and 8001.
The load balancer will route traffic to the appropriate application based on that port.
The load balancer (Nginx) will expose his internal 80 port to 80 of our localhost, so we can access the application from http://localhost:80
- Internally the Nginx will handle both the wordpress applications from port 80 - 8000  in a round-robin fashion.

* Just spin up the server with docker-compose up -d and you are good to go.

*  Access Nginx server on port 8085 , running as proxy for wordpress application on `http://localhost:8085/`

* [ Hint :] If Facing issue Check your project url and app url and make sure they are correct.
* [If Issue Persists ->  try running `localhost:8000` or `http://127.0.0.1:8000` in New Incognito Tab ]
