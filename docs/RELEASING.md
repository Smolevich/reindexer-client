# Releasing

Hard rule: **a tag is only ever pushed onto a fully green master.** A green PR is
not enough — the post-merge runs on master are built from a different merge
commit and are the ones that catch environment-dependent and order-dependent
failures (this is exactly how the original v3.0.1 tag ended up red and had to
be deleted).

## Checklist

1. Merge the PR (all its checks green).
2. Wait for **all** post-merge workflow runs on `master` to finish green:
   ```bash
   gh run list --branch master --limit 5
   # every row for the merge commit must be: completed / success
   ```
   That means all three workflows: `Tests`, `Mutation`, `Lint`.
3. Only then tag the verified SHA and publish:
   ```bash
   git tag vX.Y.Z <green-sha>
   git push origin vX.Y.Z
   gh release create vX.Y.Z --title "..." --notes "..."
   ```
4. Check that the tag-triggered runs are green as well; a release whose own
   pipeline is red gets deleted and re-issued under the next patch version —
   never reuse a deleted tag name (Packagist and caches may have already seen it).

## Versioning

- Patch (`x.y.Z`) — bug fixes, test fixes, docs.
- Minor (`x.Y.0`) — new API surface (see the roadmap in
  [api-coverage.md](api-coverage.md) / issue #44).
- Major (`X.0.0`) — BC breaks; document them in README "Upgrading" and CHANGELOG.
