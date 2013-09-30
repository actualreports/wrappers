<?php
  // NB!  jQuery Wrapper depends on PHP wrapper
  require_once '../php/ActualReportsApi.php';
  require_once 'ActualReportsPlugin.php';

  $config = array(
    'url' => 'https://app.actualreports.com/api',
    'key' => '61e5f04ca1794253ed17e6bb986c1702',
    'secret' => '68db1902ad1bb26d34b3f597488b9b28',
    'email' => 'demo@actualreports.com'
  );

  $plugin = new ActualReportsPlugin($config['key'], $config['secret'], $config['url']);
  $plugin->setEmail($config['email']);
  $plugin->setData('[
    {
        "name": "Product 1",
        "code": "T1232312",
        "code2": "TALH411099731",
        "groupName": "Group name",
        "price": 24,
        "discount": 20,
        "active": 1,
        "displayedInWebshop": 1,
        "unitName": "kg",
        "brandName": "BASIC 1",
        "length": "5.375",
        "width": "3",
        "height": "2",
        "size": "M",
        "color": "red",
        "grossWeight": "0.328",
        "description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis consequat imperdiet dolor, et iaculis erat venenatis at. Nulla facilisi. Nunc quam sem, porttitor eu viverra et, vulputate sit amet justo. Nullam rhoncus volutpat orci vitae tincidunt. Suspendisse vulputate enim adipiscing enim molestie a varius odio vulputate."
    },
    {
        "name": "Product 2",
        "code": "T1232312",
        "code2": "TALH411099731",
        "groupName": "Group name",
        "price": 14,
        "discount": 10,
        "active": 1,
        "displayedInWebshop": 1,
        "unitName": "kg",
        "brandName": "BASIC 3",
        "length": "5.375",
        "width": "3",
        "height": "2",
        "size": "M",
        "color": "red",
        "grossWeight": "0.328",
        "description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis consequat imperdiet dolor, et iaculis erat venenatis at. Nulla facilisi. Nunc quam sem, porttitor eu viverra et, vulputate sit amet justo. Nullam rhoncus volutpat orci vitae tincidunt. Suspendisse vulputate enim adipiscing enim molestie a varius odio vulputate."
    },
    {
        "name": "My Product Name 1",
        "code": "T1232312",
        "code2": "TALH411099731",
        "groupName": "Group name",
        "price": 40,
        "discount": 35,
        "active": 1,
        "displayedInWebshop": 1,
        "unitName": "kg",
        "brandName": "BASIC 2",
        "length": "5.375",
        "width": "3",
        "height": "2",
        "size": "M",
        "color": "red",
        "grossWeight": "0.328",
        "description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis consequat imperdiet dolor, et iaculis erat venenatis at. Nulla facilisi. Nunc quam sem, porttitor eu viverra et, vulputate sit amet justo. Nullam rhoncus volutpat orci vitae tincidunt. Suspendisse vulputate enim adipiscing enim molestie a varius odio vulputate."
    }
]');

  if (isset($_GET['action']))
  {
    if ($_GET['action'] == 'templates')
    {
      $plugin->templates();
    }
    else if ($_GET['action'] == 'editor')
    {
      $plugin->editor();
    }
    else if ($_GET['action'] == 'download')
    {
      $plugin->download();
    }
    else if ($_GET['action'] == 'inline')
    {
      $plugin->inline();
    }
    exit;
  }
?>
<html>
<head></head>
<body>
  <div class="toolbar">
    <button data-action="create">Create new layout</button>
    <select data-action="templates"><select>
    <button data-action="edit">Edit</button>
    <button data-action="preview">Preview</button>
    <button data-action="print">Print</button>
    <button data-action="download" data-type="pdf">Download PDF</button>
    <button data-action="download" data-type="html">Download HTML</button>
  </div>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script src="actualreports-0.1.js"></script>
  <script>
    $(function() {
      $('.toolbar').actualreports({
        endpoints: {
          editor: '?action=editor',
          templates: '?action=templates',
          inline: '?action=inline',
          download: '?action=download',
          print: '?action=print'
        },

        beforeRequest: function() {
          // Send some extra params with request
          this.setExtraParams({
            ids: [1,2,3];
          });

          // Return false to stop request
          return true;
        }
      });
    });
  </script>
</body>
</html>