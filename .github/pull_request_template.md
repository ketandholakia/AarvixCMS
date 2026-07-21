---
name: Pull Request
about: Describe your changes
---

## Summary
<!-- Brief description of what this PR does -->

## Type of Change
- [ ] Bug fix
- [ ] New feature (Phase: ___)
- [ ] Refactoring
- [ ] Documentation

## Checklist
- [ ] Feature tests added/updated and passing locally
- [ ] No N+1 queries introduced (check with query log or telescope)
- [ ] Policies updated if new resource or action added
- [ ] HTMLPurifier applied if any new `{!! !!}` output added
- [ ] `php artisan test` passes locally
- [ ] `./vendor/bin/pint` run and clean
- [ ] Migration has corresponding indexes per schema plan

## Related Phase / Tasks
<!-- e.g. Phase 2, Day 3 — TinyMCE Integration -->
