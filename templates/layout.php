<html>
  <head>
    <meta charset="UTF-8">
    <title>Cocomore IT </title>
    <meta name="author" content="Olav Schettler">
    <style type="text/css" media="screen">
      * {
        font-family: Arial, helvetica, sans-serif;
      }
      body {
        margin: 0;
        padding: 0;
      }
      #content {
        width: 960px;
        background: #EEE;
        margin: auto;
        padding: 0 10px;
      }
      footer {
        border-top: solid 1px #CCC;
        padding-bottom: 20px;
      }
      th, td {
        border-bottom: solid 1px #CCC;
        padding: 4px 10px;
      }
      tr:nth-child(odd) {
        background-color: #EEE;
      }
      .moddate {
        color: #888;
      }
      table {
        margin-bottom: 10px;
      }
      #user {
        float:right;
      }
      #nav {
        padding-top: 40px;
        float: left;
        width: 150px;
      }
      #main {
        background-color: white;
        margin-left: 150px;
        padding: 20px 10px 0 20px;
        border-left: solid 1px #CCC;
        min-height: 200px;
      }
      #pager {
        border-bottom: solid 1px #CCC;
        padding-bottom: 4px;
        margin-bottom: 10px;
        text-align: right;
      }
      .clear {
        clear: both;
      }
      .input {
        margin: 0 10px 10px 0;
        
        float: left;
      }
      label {
        display: block;
      }
      .input p {
        margin: 0;
        font-size: 0.8em;
      }
      .error {
        color: red;
      }
    </style>
  </head>
  <body>
    <div id="content">
      <div id="nav">
        <?php echo render('_nav'); ?>
      </div>
      <div id="main">
        <?php if (!empty($_SESSION['username'])): ?>
          <div id="user">
            User: <strong><?php echo $_SESSION['username']; ?></strong>
          </div>
        <?php endif; ?>
        <?php echo $contents; ?>
      </div>
      <footer>
        <p>Fragen? <a href="mailto:olav.schettler@cocomore.com">Olav Schettler</a> - <a href="http://cm.schettler.net/fossil">Code</a></p>
      </footer>
    </div>
  </body>
</html>
