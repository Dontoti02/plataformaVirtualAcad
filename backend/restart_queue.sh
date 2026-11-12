#!/bin/bash
if pm2 list | grep -q 'queue'; then
    pm2 delete queue
fi

pm2 start queue.yml