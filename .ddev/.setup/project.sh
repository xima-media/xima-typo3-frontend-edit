# .ddev/.setup/project.sh — repo-owned customizations for `ddev install`.
#
# Sourced by utils.sh on every install; survives `ddev add-on get` upgrades
# since it isn't managed by the add-on itself. See project.sh.example for the
# full set of available hooks.

# bk2k/bootstrap-package: themed sitepackage base for local dev.
# b13/container: provides the tx_container_parent field and container CTypes
# exercised by Tests/Acceptance/Fixtures/demo-content.sql and the frontend
# drag & drop / container E2E coverage.
ADDITIONAL_PACKAGES=(
    'bk2k/bootstrap-package:*'
    'b13/container:*'
)
