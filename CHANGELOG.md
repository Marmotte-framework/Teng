# Changelog

## v1.1.4

*2023-05-29*

- Fix bug with script inside base blocks

## v1.1.3

*2023-05-28*

- Remove asset function (move it to Core)

## v1.1.2

*2023-05-27*

- Rename config file to `teng.yml`

## v1.1.1

*2023-05-18*

- Explicitly require version 1.1.4 for `marmotte/http`

## v1.1.0

*2023-05-18*

- Engine handle absolute path
- Implicitly require `ext-mbstring` in `composer.json`
- Base templates
- Change `erusev/parsedown` for `league/commonmark`
- Includes
- Engine can have values common to each template
- Add function `asset` to access easily to public assets

## v1.0.0

*2023-05-15*

- Add Engine Service
- Add Parser for HTML and Markdown
