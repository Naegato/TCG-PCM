Le projet utilise Docker pour son environnement d’exécution.

Lancer les tests:
```bash
make tests
```

Si tu modifies le GameEngine, il y a des tests de replays:
```bash
make tests-replays
```

Si tu rajoutes des cartes, il faut regénérer la liste
```bash
make card-list
```

Après avoir modifié le code API,
format
```bash
make format

```
lint
```bash
make lint
```

lint-fix
```bash
make lint-fix
```

static analysis
```bash
make stan
```
