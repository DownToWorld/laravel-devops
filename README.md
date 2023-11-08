### Installation
```
composer require "downtoworld/laravel-devops:*"
```

Publish the **required** files:
```
php artisan vendor:publish --tag=laravel-devops
```

### Example with Portainer and Cloudflare Tunnels
- Create the Docker network `cloudflared` (bridge)
- Deploy the Docker container as specified @ Cloudflare -> Zero Trust -> Access -> Tunnels -> Create a tunnel (**Additionally**: attach the container to the previously created network by specifying `--network cloudflared`)
- Create a Git-repo based Stack @ Portainer webUI
    - At **Compose path** specify: `docker-compose-prod.yml`
    - Enable **GitOps updates**
    - Fill the required environment variables:
        - `APP_DOMAIN`: domain of the app in production Example: *yourdomain.com*
        - `APP_DOCKER_STACK`: the name of the stack you are configuring @ Portainer. Example: *mystack*
        - [*You can also configure here any Laravel env variables like `APP_NAME` or `APP_DEBUG`*]
    - *Deploy the stack*
- Add public hostnames to the tunnel @ Cloudflare:
    - **Webpage (Nginx)**: *yourdomain.com* HTTP *mystack*-nginx-1:80
    - **S3 Storage (Minio)**: *cdn.yourdomain.com* HTTP *mystack*-minio-1:9000
    - **Websocket server (Soketi)**: *ws.yourdomain.com* HTTPS (tls-check-disabled and ws-enabled options) *mystack*-soketi-1:6001

### Accessing private services (MySQL, Redis, etc) locally
- Run `docker run -d --name cloudflare-docker-dns --restart always --network cloudflared -e DNS_FORWARDER=127.0.0.11 cytopia/bind` and copy it's assigned IP (*your-assigned-ip*) from Portainer UI.
- Go to Portainer networks and copy `cloudflared` assigned IPV4 IPAM Subnet (*your-network-ip-range*)
- Go to Cloudflare -> Zero Trust -> Access -> Tunnels and configure a new `Private network` at your tunnel with `CIDR`: *your-network-ip-range*
- Go to Cloudflare Zero Trust Settings -> WARP Client -> Configure "Default" Device Settings:
    - Add a Local Domain Fallback: `domain`: cloudflared `DNS Servers`: *your-assigned-ip*
    - Set Split Tunnels to `Include IPs and domains` and add `Selector`: IP Address `Value`: *your-network-ip-range*
- Give your email access at Cloudflare Zero Trust Settings -> WARP Client -> Device enrollment permissions.
- Install [Cloudflare WARP](https://1.1.1.1/) on your computer, connect it to your Zero Trust org and enable it.
- Now you can access all your cloudflared-network-connected docker containers locally as `mystack-service-1.cloudflared:port`

The list of services you can access:
- *mystack*-mysql-1.cloudflared:3306 `User`: root `Password`: secret
- *mystack*-redis-1.cloudflared:6379 no-password
- http://*mystack*-seq-1.cloudflared
- http://*mystack*-minio-1.cloudflared:8900
- http://*mystack*-mailpit-1.cloudflared:8025
- http://*mystack*-meilisearch-1.cloudflared:7700

### Environment variables
Application environment variables can be managed at `docker-compose-prod.env` file.

### Queue and Scheduler
**Scheduler** and **Horizon** supervisors are running on the background automatically. You can set your own daemons/tasks by modifying `docker/supervisord.conf`

### PHP Versions, Extensions and INI files
8.2 version is running by default but can be switched to `8.1` or `7.4` using the `APP_PHP_VERSION` environment variable.
CLI PHP (used by scheduler and horizon) configs can also be modified at `docker/8.2-prod/php.ini`. 

### CI/CD Script
Deployment script can be modified at `docker/start-container` file.

### Thank yous
- Big one for Cloudflare Team for making this possible for free.
- Portainer project made it possible with their GitOps updates.
- This project was inspired on Laravel Sail by Laravel Team.
