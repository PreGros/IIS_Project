<?php

class test{
    public function __construct(private string $text, private int $id, public string $notMapped) {}

    public function getText(): string
    {
        return $this->text;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setText(string $val)
    {
        $this->text = $val;
    }

    public function setId(int $val)
    {
        $this->id = $val;
    }
}

class test2{
    private string $text;
    private int $id;

    public function getText(): string
    {
        return $this->text;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setText(string $val)
    {
        $this->text = $val;
    }

    public function setId(int $val)
    {
        $this->id = $val;
    }
}

class AutoMapper{

    /** @var array<object> $entities */
    private static array $entities = [];
    
    public static function map(object|array $srcObject, string|object $dst, ?array $map = null, bool $trackEntity = true): object
    {
        if (is_string($dst)){
            if (!class_exists($dst)){
                throw new InvalidArgumentException("Invalid class name");
            }
            $dst = self::$entities[spl_object_id($srcObject)] ?? new $dst();
        }
        
        $dstRC = new ReflectionClass($dst);
        
        if (is_object($srcObject)){
            $srcRC = new ReflectionClass($srcObject);
            $mapped = self::mapObjectFromGetters($srcRC, $dstRC, $srcObject, $dst, $map);
            self::mapObjectFromProperties($srcRC, $dstRC, $srcObject, $dst, $map, $mapped);

            if ($trackEntity){
                self::$entities[spl_object_id($dst)] = $srcObject;
            }
            return $dst;
        }
        
        self::mapObjectFromArray($dstRC, $dst, $srcObject, $map);
        return $dst;
    }

    private static function mapObjectFromGetters(
        \ReflectionClass $srcRC,
        \ReflectionClass $dstRC,
        object $srcObject,
        object $dstObject,
        ?array $map = null): array
    {
        $mapped = [];
        foreach ($srcRC->getMethods(ReflectionMethod::IS_PUBLIC) as $getter){
            if ($getter->isAbstract() || $getter->isStatic() || !str_starts_with($getter->getShortName(), 'get')){
                continue;
            }
            $property = lcfirst(substr($getter->getShortName(), strlen('get')));
            if (!($map === null || in_array($property, $map))){
                continue;
            }

            $methodName = 'set' . ucfirst($property);
            if ($dstRC->hasMethod($methodName)){
                $dstRC->getMethod($methodName)->invoke($dstObject, $getter->invoke($srcObject));
                $mapped[] = $property;
                continue;
            }

            if ($dstRC->hasProperty($property)){
                $dstRC->getProperty($property)->setValue($dstObject, $getter->invoke($srcObject));
                $mapped[] = $property;
            }
        }
        return $mapped;
    }

    private static function mapObjectFromProperties(
        \ReflectionClass $srcRC,
        \ReflectionClass $dstRC,
        object $srcObject,
        object $dstObject,
        ?array $map = null,
        array $mapped)
    {
        foreach ($srcRC->getProperties() as $property){
            $propName = $property->getName();
            if (in_array($propName, $mapped) || !($map === null || in_array($propName, $map))){
                continue;
            }

            $methodName = 'set' . ucfirst($propName);
            if ($dstRC->hasMethod($methodName)){
                $dstRC->getMethod($methodName)->invoke($dstObject, $property->getValue($srcObject));
                continue;
            }

            if ($dstRC->hasProperty($propName)){
                $dstRC->getProperty($propName)->setValue($dstObject, $property->getValue($srcObject));
            }
        }
    }

    private static function mapObjectFromArray(
        \ReflectionClass $dstRC,
        object $dst,
        array $src,
        ?array $map)
    {
        foreach ($src as $key => $value){
            if ($map !== null && !in_array($key, $map)){
                continue;
            }

            $methodName = 'set' . ucfirst($key);
            if ($dstRC->hasMethod($methodName)){
                $dstRC->getMethod($methodName)->invoke($dst, $value);
                continue;
            }

            if ($dstRC->hasProperty($key)){
                $dstRC->getProperty($key)->setValue($dst, $value);
            }
        }
    }

    public static function mapNotRC(object|array $srcObject, string|object $dst, array $map = null, bool $trackEntity = true): object
    {
        if (is_string($dst)){
            if (!class_exists($dst)){
                throw new InvalidArgumentException("Invalid class name");
            }
            $dst = self::$entities[spl_object_id($srcObject)] ?? new $dst();
        }
        
        if (is_object($srcObject)){
            $mapped = [];
            foreach (get_class_methods($srcObject) as $getter){
                if (!str_starts_with($getter, 'get')){
                    continue;
                }
                $property = lcfirst(substr($getter, strlen('get')));

                if ($map !== null && !in_array($property, $map)){
                    continue;
                }

                $methodName = 'set' . ucfirst($property);
                if (method_exists($dst, $methodName)){
                    $dst->$methodName($srcObject->$getter());
                    $mapped[] = $property;
                    continue;
                }
                if (property_exists($dst, $property)){
                    $dst->$property = $srcObject->$getter();
                    $mapped[] = $property;
                }
            }

            foreach (get_object_vars($srcObject) as $property => $value){
                if (in_array($property, $mapped) || !($map === null || in_array($property, $map))){
                    continue;
                }
                
                $methodName = 'set' . ucfirst($property);
                if (method_exists($dst, $methodName)){
                    $dst->$methodName($srcObject->$property);
                    continue;
                }
                if (property_exists($dst, $property)){
                    $dst->$property = $srcObject->$property;
                }
            }

            if ($trackEntity){
                self::$entities[spl_object_id($dst)] = $srcObject;
            }
            return $dst;
        }
        
        foreach ($srcObject as $key => $value){
            if ($map !== null && !in_array($key, $map)){
                continue;
            }

            $methodName = 'set' . ucfirst($key);
            if (method_exists($dst, $methodName)){
                $dst->$methodName($value);
            }
        }

        if ($trackEntity){
            self::$entities[spl_object_id($dst)] = $srcObject;
        }
        return $dst;
    }
}

function map(object|array $srcObject, string $dstObjectClassName, array $map = null): object
{
    if (!class_exists($dstObjectClassName)){
        throw new InvalidArgumentException("Invalid class name");
    }
    $dst = new $dstObjectClassName();
    $dstRC = new ReflectionClass($dst);
    
    if (is_object($srcObject)){
        foreach ((new ReflectionClass($srcObject))->getMethods(ReflectionMethod::IS_PUBLIC) as $getter){
            if (
                $getter->isAbstract() ||
                $getter->isStatic() ||
                !str_starts_with($getter->getShortName(), 'get') ||
                ($map !== null && !in_array($getter->getShortName(), $map))
            ){
                continue;
            }
            $name = substr($getter->getShortName(), strlen('get'));
            // $property = lcfirst($name);

            // if ($dstRC->hasProperty($property)){
            //     $prop = $dstRC->getProperty($property);
            //     $prop->getType();
            // }

            $methodName = 'set' . $name;
            if ($dstRC->hasMethod($methodName)){
                // $srcType = $getter->getReturnType();
                // $srcType = $srcType instanceof ReflectionUnionType ? $srcType->getTypes() : [$srcType];
                // $mRC = $dstRC->getMethod($methodName);
                // $dstType = $mRC->getParameters()[0]->getType();
                // $dstType = $dstType instanceof ReflectionUnionType ? $dstType->getTypes() : [$dstType];
                $dstRC->getMethod($methodName)->invoke($dst, $getter->invoke($srcObject));
            }
        }
        return $dst;
    }
    
    foreach ($srcObject as $key => $value){
        if ($map !== null && !in_array($key, $map)){
            continue;
        }

        $methodName = 'set' . ucfirst($key);
        if ($dstRC->hasMethod($methodName)){
            $dstRC->getMethod($methodName)->invoke($dst, $value);
        }
    }

    return $dst;
}

// function checkType(ReflectionMethod $src, ReflectionMethod|ReflectionProperty $dst)
// {

// }

function shave(string $str): string
{
    $transliterator = \Transliterator::createFromRules(
        ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;',
        \Transliterator::FORWARD
    );
    return $transliterator->transliterate($str);
}

$testSrc = new test("hello", 1, "hmm");

// $t = microtime(true);

// for ($i = 0; $i < 100000; $i++) { 
//     /** @var test2 */
//     $ent = AutoMapper::mapNotRC($testSrc, test2::class);
//     //var_dump($ent);

//     $ent->setText("Hello world");

//     //var_dump(AutoMapper::map($ent, $testSrc, trackEntity: false));
//     /** @var test */
//     if (AutoMapper::mapNotRC($ent, $testSrc::class, trackEntity: false)->notMapped !== "hmm"){
//         echo("error");
//         break;
//     }
// }

// var_dump(microtime(true) - $t);

// $t = microtime(true);

// for ($i = 0; $i < 100000; $i++) { 
//     /** @var test2 */
//     $ent = AutoMapper::map($testSrc, test2::class);
//     //var_dump($ent);

//     $ent->setText("Hello world");

//     //var_dump(AutoMapper::map($ent, $testSrc, trackEntity: false));
//     /** @var test */
//     if (AutoMapper::map($ent, $testSrc::class, trackEntity: false)->notMapped !== "hmm"){
//         echo("error");
//         break;
//     }
// }

// var_dump(microtime(true) - $t);

var_dump($testSrc);
$ent = AutoMapper::map($testSrc, test2::class);
var_dump($ent);
$ent->setText("Hello world");

print_r(AutoMapper::map($ent, test::class, trackEntity: false));

var_dump(shave("áhoj"));

enum TestEnum: int
{
    case A = 1;
    case B = 2;
}

class Src
{
    public ?int $val;
}

class Dst
{
    private ?TestEnum $val;

    public function setVal(TestEnum|int|null $val){
        if (is_int($val)){
            $this->val = TestEnum::tryFrom($val);
            return;
        }
        $this->val = $val;
    }

    public function getVal(bool $enum = false){
        return $enum ? $this->val : $this->val?->value;
    }
}

$o = new Dst();
$o->setVal(TestEnum::cases()[0]);

var_dump(array_combine(array_column(TestEnum::cases(), 'value'), TestEnum::cases()));

var_dump(AutoMapper::map($o, Src::class));

//var_dump(map(['id' => 2, 'text' => 'world'], test2::class));

$count = 100;

$odd = $count % 2 !== 0;
$n = $odd ? $count + 1 : $count;

$a1 = [];
$t = microtime(true);

$l = new \SplDoublyLinkedList();
for ($i = 0; $i < $n; $i++){
    $l->push($i);
}

for ($i = 0; $i < $n - 1; $i++){
    for ($j = 0; $j < $n / 2; $j++){
        $first = $l->offsetGet($j);
        $second = $l->offsetGet($n - $j - 1);
        if (!$odd || ($first !== $n - 1 && $second !== $n - 1)){
            //echo($first . '-' . $second . "\n");
            $a1[] = [$first, $second];
        }
    }
    $l->add(1, $l->pop());
}
$t1 = microtime(true) - $t;
echo("\n");

/**
 * Rotates $number in sequence form zero to $count by $by positions
 */
function rotateRight(int $number, int $count, int $by)
{
    return (!$count || $number > $count || $number < 0) ? null : (($number - $by) + $count * ceil($by / $count)) % $count;
}

$a2 = [];

$t = microtime(true);
for ($i = 0; $i < $n - 1; $i++){
    for ($j = 0; $j < ($n / 2); $j++){
        $f = $j === 0 ? 0 : rotateRight($j - 1, $n - 1, $i) + 1; //((($j - 1 - $i) + $n - 1) % ($n - 1)) + 1;
        $sn = $n - $j - 1;
        $s = $sn === 0 ? 0 : rotateRight($sn - 1, $n - 1, $i) + 1; //((($sn - 1 - $i) + $n - 1) % ($n - 1)) + 1;
        if (!$odd || ($f !== $n - 1 && $s !== $n - 1)){
            //echo($f . '-' . $s . "\n");
            $a2[] = [$f, $s];
        }
    }
}
$t2 = microtime(true) - $t;

$a2 = [];

/** 0, 1, 2, 3 -> 0, 1, 2 */

$t = microtime(true);
for ($i = 0; $i < $n - 1; $i++){
    for ($j = 0; $j < ($n / 2); $j++){
        $f = $j === 0 ? 0 : ((($j - 1 - $i) + $n - 1) % ($n - 1)) + 1;
        $sn = $n - $j - 1;
        $s = $sn === 0 ? 0 : ((($sn - 1 - $i) + $n - 1) % ($n - 1)) + 1;
        if (!$odd || ($f !== $n - 1 && $s !== $n - 1)){
            $a2[] = [$f, $s];
        }
    }
}
$t3 = microtime(true) - $t;

if ($a1 === $a2){
    echo("same");
}
else{
    echo("not same");
}

echo("\ntime1: {$t1} time2: {$t2} time3: {$t3}\n");

/** 1, 2, 3, 4 */
/** -2, -1, 0, 1  */
/** 1, 2, 3, 4 */
/** 4, 5, 6, 7 */
//echo(rotateRight(0, 4, 2));

// for ($k = 0; $k < $n - 1; $k++){
//     for ($i = 0; $i < $n / 2; $i++){
//         $j = $i - $k;
//         if ($j == 0){
//             echo($j . '-' . ($j - $k - 1) % $n . "\n");
//         }
//         else if ( $j > 0 ){
//             echo($j . '-' . ($j - (($i * 2) + 1)) % $n . "\n");
//         }
//         else if ( $j < 0 ){
//             $j = $j % $n;
//             echo($j . '-' . ($j - (($i + 1) * 2)) . "\n");
//         }
//     }
// }

function printLinkedList(\SplDoublyLinkedList $list){
    for ($list->rewind(); $list->valid(); $list->next()) {
        echo($list->current() . " ");
    }
    echo("\n");
}

//$t = microtime(true);
$countOfParticipants = 9;

$l = new \SplDoublyLinkedList();
for ($i = 0; $i < $countOfParticipants; $i++){
    $l->push($i);
}

class MatchT
{
    private ?MatchT $parent1 = null;
    private ?MatchT $parent2 = null;
    private ?int $participant1 = null;
    private ?int $participant2 = null;

    public function __construct(?MatchT $parent1 = null, ?MatchT $parent2 = null, ?int $participant1, ?int $participant2)
    {
        $this->parent1 = $parent1;
        $this->parent2 = $parent2;
        $this->participant1 = $participant1;
        $this->participant2 = $participant2;
    }
}

class MatchT2
{
    private ?MatchT2 $childMatch = null;
    private ?int $participant1 = null;
    private ?int $participant2 = null;

    public function __construct(?int $participant1, ?int $participant2)
    {
        $this->participant1 = $participant1;
        $this->participant2 = $participant2;
    }

    public function setChildMatch(?MatchT2 $match)
    {
        $this->childMatch = $match;
    }

    public function getChildMatch(): ?MatchT2
    {
        return $this->childMatch;
    }
}

$matches = [];
$fromLeft = true;
$c = $countOfParticipants;
$index = 0;
$level = 0;
while (($count = $l->count()) > 1){
    $matchesPerLevel = floor($count / 2);
    $oddMatches = $count % 2;

    if ($fromLeft){
        for ($i = 0; $i < $matchesPerLevel; $i++){
            // $f = $l->shift();
            // $s = $l->shift();
            // $match = new MatchT(
            //     $f >= $countOfParticipants ? $matches[$f - $countOfParticipants] : null,
            //     $s >= $countOfParticipants ? $matches[$s - $countOfParticipants] : null,
            //     $f < $countOfParticipants ? $f : null,
            //     $s < $countOfParticipants ? $s : null
            // );
            $f = $l->shift();
            $s = $l->shift();
            $match = new MatchT2(
                $f >= $countOfParticipants ? null : $f,
                $s >= $countOfParticipants ? null : $s
            );
            if ($f >= $countOfParticipants){
                $matches[$f - $countOfParticipants]['next_match'] = $index;
            }
            if ($s >= $countOfParticipants){
                $matches[$s - $countOfParticipants]['next_match'] = $index;
            }
            $matches[$index++] = ['match' => $match, 'winner' => $c, 'level' => $level, 'next_match' => null];
            $l->push($c++);
            // echo($matches[$index - 1][0] . ' ' . $matches[$index - 1][1] . "\t");
        }

        if ($oddMatches){
            $l->push($l->shift());
        }
    }
    else{
        // $str = "";
        for ($i = $matchesPerLevel - 1; $i >= 0; $i--){
            // $f = $l->pop();
            // $s = $l->pop();
            // $match = new MatchT(
            //     $f >= $countOfParticipants ? $matches[$f - $countOfParticipants] : null,
            //     $s >= $countOfParticipants ? $matches[$s - $countOfParticipants] : null,
            //     $f < $countOfParticipants ? $f : null,
            //     $s < $countOfParticipants ? $s : null
            // );
            $f = $l->pop();
            $s = $l->pop();
            $match = new MatchT2(
                $f >= $countOfParticipants ? null : $f,
                $s >= $countOfParticipants ? null : $s
            );
            if ($f >= $countOfParticipants){
                $matches[$f - $countOfParticipants]['next_match'] = $index + $i;
            }
            if ($s >= $countOfParticipants){
                $matches[$s - $countOfParticipants]['next_match'] = $index + $i;
            }
            $matches[$index + $i] = ['match' => $match, 'winner' => $c, 'level' => $level, 'next_match' => null];
            $l->unshift($c++);
            // $str = ($matches[$index + $i][0] . ' ' . $matches[$index + $i][1] . "\t") . $str;
        }

        // echo($str);
        $index += $matchesPerLevel;
        if ($oddMatches){
            $l->unshift($l->pop());
        }
    }
    //echo("\n");
    $fromLeft = !$fromLeft;
    $level++;
}

//var_dump($matches);
// $t1 = microtime(true) - $t;

// echo("time: {$t1}\n");

//echo($l->pop() . ' WIENER' . "\n");

// $l = 0;
// for ($i = 0; $i < count($matches); $l++){
//     while ($i < count($matches) && $matches[$i]['level'] === $l){
//         echo($matches[$i][0] . ' ' . $matches[$i][1] . "\t");
//         $i++;
//     }
//     echo("\n");
// }

// $c = 8;
// $temp = 0;
// for ($i = 0; $i < count($matches);){
//     while ($i < count($matches) && $matches[$i][0] <= $c && $matches[$i][1] <= $c){
//         echo($matches[$i][0] . ' ' . $matches[$i][1] . "\t");
//         if ($matches[$i]['winner'] > $temp){
//             $temp = $matches[$i]['winner'];
//         }
//         $i++;
//     }
//     echo("\n");
//     $c = $temp;
// }

$m = [];
for ($i = 0; $i < count($matches); $i++){
    $match = $matches[$i];
    $match['match']->setChildMatch(($matches[$match['next_match']] ?? [])['match'] ?? null);
    $m[] = $match['match'];
    //var_dump($match['match']);
}

//var_dump($m);

$tree = [
    ['id' => 1, 'child' => 7],
    ['id' => 2, 'child' => 6],
    ['id' => 3, 'child' => 6],
    ['id' => 4, 'child' => 5],
    ['id' => 5, 'child' => 7],
    ['id' => 6, 'child' => 8],
    ['id' => 7, 'child' => 8],
    ['id' => 8, 'child' => null]
];
$layers = [];
$layer = 0;

foreach ($tree as $node){ 
    if (in_array($node['id'], isset($layers[$layer]) ? array_column($layers[$layer], 'child') : [])){
        if ($layer !== 0){
            $children = array_column($layers[$layer - 1], 'child');
            usort($layers[$layer], function (mixed $a, mixed $b) use ($children){
                return
                    (($val = array_search($a['id'], $children)) === false ? PHP_INT_MAX : $val)
                        <=>
                    (($val = array_search($b['id'], $children)) === false ? PHP_INT_MAX : $val);
            });
        }
        $layer++;
    }
    $layers[$layer][] = ['id' => $node['id'], 'child' => $node['child']];
}

// var_dump($layers);

// $pos = [];
// for ($i = 0; $i < count($layers); $i++){
//     for ($j = 0; $j < count($layers[$i]); $j++){
//         echo(str_repeat(' ', $j === 0 ? $pos[$layers[$i][$j]['id']] ?? 0 : 0));
//         echo(' ' . $layers[$i][$j]['id']);
//         if (isset($pos[$layers[$i][$j]['child']])){
//             $pos[$layers[$i][$j]['child']] = floor(($pos[$layers[$i][$j]['child']] + $j)/2);
//         }
//         else if (isset($layers[$i][$j]['child'])){
//             $pos[$layers[$i][$j]['child']] = $j;
//         }
//     }
//     echo("\n");
// }
// var_dump($layers);

$obj = new MatchT2(null, null);
var_dump(spl_object_id($obj));
var_dump(spl_object_id(clone $obj));

