FROM node:14

WORKDIR /srv/app.blackfire.io/assets

RUN npm install -g yarn@1.22.15 --force
CMD yarn install --pure-lockfile --frozen-lockfile --emoji && yarn dev-server

RUN curl -Lo /usr/local/bin/gosu https://github.com/tianon/gosu/releases/download/1.10/gosu-amd64 && chmod +x /usr/local/bin/gosu

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
