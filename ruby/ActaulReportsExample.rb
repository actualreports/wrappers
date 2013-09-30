
## Include ActualReports class
load("ActualReportsApi.rb")

## Initialize class and enter configuration
api = ActualReportsApi.new()
api.setApiKey("61e5f04ca1794253ed17e6bb986c1702")
api.setPrivateKey("68db1902ad1bb26d34b3f597488b9b28")
api.setEmail("demo@actualreports.com")

## Get Templates
## returns hash or string (depends on query)
templates = api.request("template") # {"id"=>2598, "name"=>"Product label", "modified"=>"2013-07-06 12:01:51", "owner"=>false, "tags"=>"Array"}

## Get Editor address
editor = api.request("editor") # https://dev.actualreports.com/api/v2/editor?apikey=61e5f04ca1794253ed17e6bb986c1702&email=demo%40actualreports.com&signature=5407158e6092a31db85a769960daf4b1d9b21afb05bf0c25800e1a4c8a33501e&timestamp=1379789529

## Make a formated output and save to file
pdf = api.request("template/2598/output", {"data" => '[{"grossWeight": 20}]', "format" => "pdf"})
File.open("testwrite.pdf",'wb') {|f| f.write(pdf) }
