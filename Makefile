

generate-grpc:
    docker run --platform linux/amd64 -v $(pwd):/defs namely/protoc-all -f reindexer.proto -l php


