<?php
require_once 'ActualReportsApi.php';
$publicKey = '61e5f04ca1794253ed17e6bb986c1702';
$privateKey = '68db1902ad1bb26d34b3f597488b9b28';
$email = 'demo@actualreports.com';

$client = new ActualReportsApi($publicKey, $privateKey);
$client->setEmail($email);

if ($_POST)
{
  $client->setData($_POST['data']);

  if (isset($_POST['editor']))
  {
    header('Location: '.$client->getEditorUrl(array(
      'template' => $_POST['template'])
    ));
  }
  else
  {
    $response = $client->request('POST', 'template/'.$_POST['template'].'/output', array(
      'format' => isset($_POST['pdf']) ? 'pdf' : 'html',
      'output' => 'base64'
    ), false);
    header('Content-type: ', $response['meta']['content-type']);
    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
    header('Pragma: public');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Content-Disposition: inline; filename="Preview.pdf"');
    die(base64_decode($response['response']));
  }
}
else
{
  $templates = $client->request('GET', 'template');
?>
  <form method="POST">
    <div>List of user templates:
      <select name="template">
        <?php
          foreach ($templates as $template)
          {
            echo '<option value="'.$template['id'].'">'.$template['name'].'</option>';
          }
        ?>
      </select>
      <button type="submit" name="pdf">PDF</button>
      <button type="submit" name="html">HTML</button>
      <button type="submit" name="editor">EDITOR</button>
    </div>
    <div><textarea name="data" style="width: 50%; height: 400px;">
      [
{
"code": "228-C12-1027",
"client": "Katharine Butler",
"clientAddress": "VERNON HILLIS IL 60061UNITED STATES",
"inboiceNr": "1",
"job": "Computers",
"rows": [
{
"code": "CHRMBK",
"name": "Chrome Book 1",
"price": 600.00,
"image": "http://cdn.cultofandroid.com/wp-content/uploads/2013/02/chromebookpixel.jpg",
"description": "Boots in seconds. Nothing complicated to learn. Comes with your favorite Google apps. Built for everyday use and perfect for sharing with others. Starting at $199.",
"subdata": [
{
"code": "123123123",
"name": "Chrome Book extended",
"description": "Some text here! Some text here! Some text here!",
"price": 200,
"amount": 2,
"tax": 10,
"total": 440
}
]
},
{
"code": "CHRMBK",
"name": "Chrome Book 1",
"price": 600.00,
"image": "http://pcw-ng.cdn.e-merchant.com/css/themes/chromebook/img/chromebook-large.jpg",
"description": "Boots in seconds. Nothing complicated to learn. Comes with your favorite Google apps. Built for everyday use and perfect for sharing with others. Starting at $199.",
"subdata": [
{
"code": "123123123",
"name": "Chrome Book extended",
"description": "Some text here! Some text here! Some text here!",
"price": 200,
"amount": 2,
"tax": 10,
"total": 440
}
]
},
{
"code": "CHRMBK",
"name": "Chrome Book 1",
"price": 600.00,
"image": "",
"description": "Boots in seconds. Nothing complicated to learn. Comes with your favorite Google apps. Built for everyday use and perfect for sharing with others. Starting at $199.",
"subdata": [
{
"code": "123123123",
"name": "Chrome Book extended",
"description": "Some text here! Some text here! Some text here!",
"price": 200,
"amount": 2,
"tax": 10,
"total": 440
}
]
},
{
"code": "CHRMBK",
"name": "Chrome Book 1",
"price": 600.00,
"image": "http://cdn.cultofandroid.com/wp-content/uploads/2013/02/chromebookpixel.jpg",
"description": "Boots in seconds. Nothing complicated to learn. Comes with your favorite Google apps. Built for everyday use and perfect for sharing with others. Starting at $199.",
"subdata": [
{
"code": "123123123",
"name": "Chrome Book extended",
"description": "Some text here! Some text here! Some text here!",
"price": 200,
"amount": 2,
"tax": 10,
"total": 440
}
]
},
{
"code": "CHRMBK",
"name": "Chrome Book 1",
"price": 600.00,
"image": "http://pcw-ng.cdn.e-merchant.com/css/themes/chromebook/img/chromebook-large.jpg",
"description": "Boots in seconds. Nothing complicated to learn. Comes with your favorite Google apps. Built for everyday use and perfect for sharing with others. Starting at $199.",
"subdata": [
{
"code": "123123123",
"name": "Chrome Book extended",
"description": "Some text here! Some text here! Some text here!",
"price": 200,
"amount": 2,
"tax": 10,
"total": 440
}
]
}
],
"dueDate": "23 Jan 2008",
"customerNr": "C12"
}
]
    </textarea></div>
  </form>
<?php
}
?>