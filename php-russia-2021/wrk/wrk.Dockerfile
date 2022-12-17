FROM ubuntu:20.04

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common build-essential \
    ca-certificates libssl-dev \
    mc htop git unzip iputils-ping

RUN git clone https://github.com/wg/wrk.git /home/wrk && \
    cd /home/wrk && \
    make && \
    cp wrk /usr/local/bin

COPY ./ /
WORKDIR /

# wrk -t2 -c200 -d10s -s post.lua http://server:80/products/list
CMD tail -f /dev/null
#ENTRYPOINT ["/usr/local/bin/wrk"]