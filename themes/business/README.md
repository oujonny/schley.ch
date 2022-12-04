## Type attributes


| Type             | Description                                                     | Default   |
| :----------------- | ----------------------------------------------------------------- | :---------- |
| title            | Set the Title of the page                                       |           |
| url              | Set the url (or anchor) of the page                             | *not set* |
| weight           | Set the weight which is used to order                           |           |
| header_menu      | Set Nav Item in navbar for this page                            | false     |
|                  |                                                                 |           |
| page_image       |                                                                 |           |
| src              | Relative path to image                                          |           |
|                  |                                                                 |           |
| background_color | Set the background for the page (spread accros the hole screen) | *not set* |

## Types of pages

There are three different types how the individual pages get rendered into the onepager.


| Type      | Description                                                                                                       | Default |
| ----------- | ------------------------------------------------------------------------------------------------------------------- | --------- |
| according | Render the markdown content and creates an acordion's tab for each subpage                                        | x       |
| carousel  | Render each subpage as a carousel slide see: https://getbootstrap.com/docs/5.2/components/carousel/#with-captions |         |
| cards     | Render suppage as cards in two collums                                                                            |         |
| generic   | just renders the markdown content plain                                                                           |         |
