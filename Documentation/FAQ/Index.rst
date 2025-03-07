..  include:: /Includes.rst.txt

.. _faq:

===
FAQ
===

.. rst-class:: panel panel-default

Why is the frontend edit menu not displayed on my page / for my content element?
=======================================

There may be a number of reasons for this:

**Backend user session**

Are you currently logged into the TYPO3 backend? Otherwise the frontend edit will not working.

**Backend user permission**

Does your user have all permissions to edit the page as well as the content elements?

**TypoScript**

Is the TypoScript template "Frontend edit" included in your sitepackage? Do you have declared the constants to restrict the usage of the frontend edit?

**Content Element IDs**

Make sure that the content element "c-ids" (Content Element IDs) are available within your frontend template, e.g. "c908".

**Content Element on current Page**

For now only all content elements on the current page are "editable". So if you're using some kind of inheritance, e.g. for your footer, this content can't be edited. Maybe I will find a smarter solution for this in the future.

**Debug**

Check the network tab for the initial ajax call (something like :code:`/?type=1729341864` with the information about the editable content elements and the according dropdown menus.


.. rst-class:: panel panel-default

After closing the edit form will I redirected to the wrong frontend location, e.g. to the root page
=======================================

This could be caused by a strict referer header in your request. If the return url could not be determined correctly, you can force the url generation by pid and language in the extension setting: :code:`forceReturnUrlGeneration`.
