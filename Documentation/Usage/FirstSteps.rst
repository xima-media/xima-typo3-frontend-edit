..  include:: /Includes.rst.txt

..  _first-steps:

===========
First steps
===========

A short tour for editors. It assumes an administrator has already
:ref:`installed <installation>` the extension for your site.

..  _first-steps-visible:

1. Log in, then open your site
==============================

Frontend editing only appears for logged-in backend users. Log into the TYPO3
backend, then open a page of your website in the same browser.

Two things appear:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   What you see
        -   What it is
    *   -   A small bar in the bottom-right corner
        -   The :ref:`toolbar <toolbar>` — actions for the **whole page**
    *   -   A pencil button when you hover a content element
        -   The :ref:`Edit Menu <edit-menu>` — actions for **that element**

..  note::

    Visitors of your website never see any of this. Nothing is injected for
    users without a backend session.

..  _first-steps-edit:

2. Change a content element
===========================

Hover the element you want to change and click its pencil button. The TYPO3 edit
form opens — either in the backend, or, if your site has
:ref:`inline editing <inline-editing>` enabled, in a panel right next to the
page.

Save with **Save & Close** and you land back on the same spot in the frontend,
with a confirmation message.

..  tip::

    The three-dot button next to the pencil holds the remaining actions: hide,
    delete, info, history, move, and "new content after".

..  _first-steps-page:

3. Change the page itself
=========================

The toolbar covers everything that concerns the page rather than a single
element — page properties, opening the page module, page info.

..  _first-steps-off:

4. Switch it off when it gets in the way
========================================

The eye icon in the toolbar turns frontend editing off. Your choice is stored
for your user and survives a reload, so you can check the page exactly as a
visitor sees it and switch back when you are done.

..  seealso::

    *   :ref:`keyboard` — operating everything without a mouse
    *   :ref:`faq` — what to do when nothing appears
