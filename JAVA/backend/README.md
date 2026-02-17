# Lego factory

This is a simulation of a Lego factory.

All the API calls are installed in the `web.go` file.

The Go backend can be compiled with the command `go build .` to create the `legofactory` executable file.

Run the backend with the following command if you want to use the `config.yml` configuration file, the server listening on localhost on the port 8000 and you want to use `storageDir` as the storage directory:

```shell
./legofactory config.yml localhost:8000 storageDir
```

To know what to include into the YAML configuration file, please refer to the `FactoryConfig` struct of the `config.go` file. You can find an example (that can be adapted) in the `config-example.yml` file.