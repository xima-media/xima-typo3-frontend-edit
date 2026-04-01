-- Demo content for xima-typo3-frontend-edit development instances
-- Provides a realistic page tree with various content element types

-- Clean slate: remove records from previous installs and default sys_template
DELETE FROM `sys_template` WHERE `pid` = 1;
DELETE FROM `tt_content` WHERE `pid` IN (1, 2, 3, 4, 5, 6, 7);
DELETE FROM `pages` WHERE `uid` IN (1, 2, 3, 4, 5, 6, 7);

-- Pages
REPLACE INTO `pages` (`uid`, `pid`, `tstamp`, `crdate`, `deleted`, `hidden`, `sorting`, `title`, `doktype`, `slug`, `is_siteroot`) VALUES
(1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'Home', 1, '/', 1),
(2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'About Us', 1, '/about-us', 0),
(3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'Services', 1, '/services', 0),
(4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 768, 'Blog', 1, '/blog', 0),
(5, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'Getting Started with TYPO3', 1, '/blog/getting-started', 0),
(6, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'Frontend Editing Tips', 1, '/blog/frontend-editing-tips', 0),
(7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 1024, 'Contact', 1, '/contact', 0);

-- Content Elements: Home (pid=1)
REPLACE INTO `tt_content` (`uid`, `pid`, `tstamp`, `crdate`, `deleted`, `hidden`, `sorting`, `CType`, `colPos`, `header`, `header_layout`, `bodytext`, `starttime`, `endtime`) VALUES
(1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'header', 0, 'Welcome to our Website', 1, '', 0, 0),
(2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'text', 0, 'About This Demo', 0,
'<p>This is a demo installation of the <strong>xima_typo3_frontend_edit</strong> extension. It provides frontend editing capabilities for TYPO3 content elements.</p>\n<p>Hover over any content element to see the edit toolbar. Click the edit button to modify content directly or use the contextual editing sidebar.</p>', 0, 0),
(3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 768, 'textmedia', 0, 'Features Overview', 0,
'<p>The extension adds convenient editing buttons to content elements in the frontend, allowing editors to:</p>\n<ul>\n<li>Quickly edit content without leaving the frontend</li>\n<li>Access the page layout module</li>\n<li>Hide, move, or delete content elements</li>\n<li>View content element history</li>\n</ul>', 0, 0),
(4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 1024, 'html', 0, 'Custom HTML Block', 0,
'<div style="background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0;"><h3>Developer Note</h3><p>This HTML content element demonstrates that custom HTML is also editable via the frontend toolbar.</p></div>', 0, 0),
(5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 1280, 'bullets', 0, 'Quick Links', 0,
'Homepage\nAbout Us\nServices\nContact', 0, 0);

-- Content Elements: About Us (pid=2)
REPLACE INTO `tt_content` (`uid`, `pid`, `tstamp`, `crdate`, `deleted`, `hidden`, `sorting`, `CType`, `colPos`, `header`, `header_layout`, `bodytext`) VALUES
(6, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'header', 0, 'About Us', 1, ''),
(7, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'text', 0, 'Our Mission', 0,
'<p>We are dedicated to making TYPO3 content editing as seamless as possible. Our frontend editing extension bridges the gap between the frontend experience and backend editing capabilities.</p>'),
(8, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 768, 'text', 0, 'Our Team', 0,
'<p>Our team consists of experienced TYPO3 developers who understand the daily challenges editors face when managing content.</p>\n<p>We believe that editing should be intuitive, fast, and contextual.</p>');

-- Content Elements: Services (pid=3)
REPLACE INTO `tt_content` (`uid`, `pid`, `tstamp`, `crdate`, `deleted`, `hidden`, `sorting`, `CType`, `colPos`, `header`, `header_layout`, `bodytext`) VALUES
(9, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'header', 0, 'Our Services', 1, ''),
(10, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'text', 0, 'TYPO3 Development', 0,
'<p>We offer professional TYPO3 development services including extension development, system integration, and performance optimization.</p>'),
(11, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 768, 'text', 0, 'Content Management Consulting', 0,
'<p>Our consulting services help you get the most out of your TYPO3 installation. We analyze your workflows and suggest improvements for efficiency.</p>'),
(12, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 1024, 'text', 0, 'Training & Workshops', 0,
'<p>We provide hands-on training for editors and administrators. Learn how to use TYPO3 effectively, including frontend editing features.</p>');

-- Content Elements: Blog Post 1 (pid=5)
REPLACE INTO `tt_content` (`uid`, `pid`, `tstamp`, `crdate`, `deleted`, `hidden`, `sorting`, `CType`, `colPos`, `header`, `header_layout`, `bodytext`) VALUES
(13, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'header', 0, 'Getting Started with TYPO3', 1, ''),
(14, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'text', 0, 'Introduction', 0,
'<p>TYPO3 is one of the most powerful open-source content management systems available. In this guide, we will walk you through the basics of getting started.</p>'),
(15, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 768, 'text', 0, 'Installation', 0,
'<p>TYPO3 can be installed via Composer, which is the recommended method for modern TYPO3 projects. Simply run <code>composer create-project typo3/cms-base-distribution</code> to get started.</p>'),
(16, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 1024, 'text', 0, 'First Steps', 0,
'<p>After installation, log into the backend at <code>/typo3</code> and start creating your first pages and content elements. The page tree on the left helps you organize your site structure.</p>');

-- Content Elements: Blog Post 2 (pid=6)
REPLACE INTO `tt_content` (`uid`, `pid`, `tstamp`, `crdate`, `deleted`, `hidden`, `sorting`, `CType`, `colPos`, `header`, `header_layout`, `bodytext`) VALUES
(17, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'header', 0, 'Frontend Editing Tips', 1, ''),
(18, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'text', 0, 'Using the Edit Toolbar', 0,
'<p>The frontend editing toolbar appears when you hover over a content element. It provides quick access to editing functions without navigating to the backend.</p>'),
(19, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 768, 'text', 0, 'Contextual Editing Sidebar', 0,
'<p>With the experimental contextual editing feature (TYPO3 v14.2+), you can edit content directly in a sidebar panel. Enable it in your site settings under "Frontend Edit".</p>'),
(20, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 1024, 'text', 0, 'Keyboard Shortcuts', 0,
'<p>Press <kbd>Escape</kbd> to close the editing sidebar. Use <kbd>Ctrl+Click</kbd> on an edit button to open the full backend editor in a new tab.</p>');

-- Content Elements: Contact (pid=7)
REPLACE INTO `tt_content` (`uid`, `pid`, `tstamp`, `crdate`, `deleted`, `hidden`, `sorting`, `CType`, `colPos`, `header`, `header_layout`, `bodytext`) VALUES
(21, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 256, 'header', 0, 'Contact Us', 1, ''),
(22, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 512, 'text', 0, 'Get in Touch', 0,
'<p>Have questions about the frontend editing extension? We would love to hear from you.</p>\n<p>Visit our <a href="https://github.com/xima-media/xima-typo3-frontend-edit">GitHub repository</a> to report issues, suggest features, or contribute to the project.</p>'),
(23, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 768, 'text', 0, 'Support', 0,
'<p>For support, please open an issue on GitHub. We aim to respond within a few business days.</p>');
