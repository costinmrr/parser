# JSON / XML / CSV Parser

This package creates a parser to extract data from JSON, XML and CSV strings using a simplified jsonpath notation.

After creating the Parser object, you can use the `parse` method to extract data from a string.

The `parse()` method can be called with the following parameters:
- `mapping`: an associative array (map) of columns to be extracted (as keys), together with the paths used for the extraction (as values)
- `content`: the string content to be parsed
- `format`: `json` / `xml` / `csv` - the format of the content
- `return_type`: `dataset` / `individual_values` - whether to response contains a homogenous dataset or just the values for each column in the mapping; defaults to `dataset`

___

### JSON mapping

For the `JSON` format, the paths used in the `mapping` are just some simple JSONPath notation.
Example:

```
mapping[items]="$.foo[*].bar"
```

where `items` is the name of the column to be extracted and `$.foo[*].bar` is the path used for the extraction.
Both `[]` and `[*]` are valid expressions for extracting from an array.

___

### XML mapping

For the `XML` format, the paths used in the `mapping` use also the JSONPath notation, with 2 additions:
- `@`: to prefix the sub-path for an attribute
- `&text`: to extract the value of a text node

Example:

```
mapping[items]="$.global_node.foo[*].@bar"
mapping[prices]="$.global_node.foo[*].&text"
```

`@bar` refers to the `bar` attribute of the node `foo`.
`&text` refers to the text value of the node `foo`.

___

### CSV mapping

For the `CSV` format, the paths used in the `mapping` have the following formats:

#### 1. csv without header

```
mapping[items]="$[0]" // first column
mapping[prices]="$[1]" // second column
...
mapping[other]="$[n]" // nth column
```

#### 2. csv with header

```
mapping[items]="$[items]" // column named "items"
mapping[prices]="$[prices]" // column named "prices"
...
mapping[other]="$[other]" // column named "other"
```

where `items`, `prices` etc. are the values of the header row.


---

##  Examples

### 1. JSON

```
$mapping = [
  "items" => "$.foo[*].bar",
  "prices" => "$.foo[*].price",
  "date" => "$.date",
];

$content = '{
  "foo": [
    {
      "bar": "item1",
      "price": 10
    },
    {
      "bar": "item2",
      "price": 20
    },
    {
      "bar": "item3",
      "price": 30
    }
  ],
  "date": "2020-01-01"
}';

$parser = (new ParserFactory())->create($mapping, $content, ContentFormat::JSON);
$resultDataset = $parser->parse();
$resultIndividualValues = $parser->parse(ReturnType::INDIVIDUAL_VALUES);

var_dump($resultDataset);
var_dump($resultIndividualValues);
```

```
array(3) {
  ["items"]=>
  array(3) {
    [0]=>
    string(5) "item1"
    [1]=>
    string(5) "item2"
    [2]=>
    string(5) "item3"
  }
  ["prices"]=>
  array(3) {
    [0]=>
    int(10)
    [1]=>
    int(20)
    [2]=>
    int(30)
  }
  ["date"]=>
  array(3) {
    [0]=>
    string(10) "2020-01-01"
    [1]=>
    string(10) "2020-01-01"
    [2]=>
    string(10) "2020-01-01"
  }
}

array(3) {
  ["items"]=>
  array(3) {
    [0]=>
    string(5) "item1"
    [1]=>
    string(5) "item2"
    [2]=>
    string(5) "item3"
  }
  ["prices"]=>
  array(3) {
    [0]=>
    int(10)
    [1]=>
    int(20)
    [2]=>
    int(30)
  }
  ["date"]=>
  string(10) "2020-01-01"
}

```   

### 2. XML

```
$mappings = [
  "items" => "$.product.prices.foo[*].@bar",
  "prices" => "$.product.prices.foo[*].&text",
  "date" => "$.product.date.&text",
];

$content = '<?xml version="1.0" encoding="UTF-8"?>
<product>
  <prices>
    <foo bar="item1">10</foo>
    <foo bar="item2">20</foo>
    <foo bar="item3">30</foo>
  </prices>
  <date>2020-01-01</date>
</product>';

$parser = (new ParserFactory())->create($mappings, $content, ContentFormat::XML);
$resultDataset = $parser->parse();
$resultIndividualValues = $parser->parse(ReturnType::INDIVIDUAL_VALUES);

var_dump($resultDataset);
var_dump($resultIndividualValues);
```

```
array(3) {
  ["items"]=>
  array(3) {
    [0]=>
    string(5) "item1"
    [1]=>
    string(5) "item2"
    [2]=>
    string(5) "item3"
  }
  ["prices"]=>
  array(3) {
    [0]=>
    string(2) "10"
    [1]=>
    string(2) "20"
    [2]=>
    string(2) "30"
  }
  ["date"]=>
  array(3) {
    [0]=>
    string(10) "2020-01-01"
    [1]=>
    string(10) "2020-01-01"
    [2]=>
    string(10) "2020-01-01"
  }
}


array(3) {
  ["items"]=>
  array(3) {
    [0]=>
    string(5) "item1"
    [1]=>
    string(5) "item2"
    [2]=>
    string(5) "item3"
  }
  ["prices"]=>
  array(3) {
    [0]=>
    string(2) "10"
    [1]=>
    string(2) "20"
    [2]=>
    string(2) "30"
  }
  ["date"]=>
  string(10) "2020-01-01"
}
```

### 3. CSV without header

```
$mappings = [
  "items" => "$[0]",
  "prices" => "$[1]",
  "date" => "$[2]",
];

$content = 'item1,10,2020-01-01
item2,20,2020-01-01
item3,30,2020-01-01';

$parser = (new ParserFactory())->create($mappings, $content, ContentFormat::CSV);
$resultDataset = $parser->parse();

var_dump($resultDataset);
```

```
array(3) {
  ["items"]=>
  array(3) {
    [0]=>
    string(5) "item1"
    [1]=>
    string(5) "item2"
    [2]=>
    string(5) "item3"
  }
  ["prices"]=>
  array(3) {
    [0]=>
    string(2) "10"
    [1]=>
    string(2) "20"
    [2]=>
    string(2) "30"
  }
  ["date"]=>
  array(3) {
    [0]=>
    string(10) "2020-01-01"
    [1]=>
    string(10) "2020-01-01"
    [2]=>
    string(10) "2020-01-01"
  }
}
```

### 4. CSV with header

```
$mappings = [
  "items" => "$.item",
  "prices" => "$.price",
  "date" => "$.date",
];

$content = 'item,price,date
item1,10,2020-01-01
item2,20,2020-01-01
item3,30,2020-01-01';

$parser = (new ParserFactory())->create($mappings, $content, ContentFormat::CSV);
$resultDataset = $parser->parse();

var_dump($resultDataset);
```

```
array(3) {
  ["items"]=>
  array(3) {
    [0]=>
    string(5) "item1"
    [1]=>
    string(5) "item2"
    [2]=>
    string(5) "item3"
  }
  ["prices"]=>
  array(3) {
    [0]=>
    string(2) "10"
    [1]=>
    string(2) "20"
    [2]=>
    string(2) "30"
  }
  ["date"]=>
  array(3) {
    [0]=>
    string(10) "2020-01-01"
    [1]=>
    string(10) "2020-01-01"
    [2]=>
    string(10) "2020-01-01"
  }
}
```
