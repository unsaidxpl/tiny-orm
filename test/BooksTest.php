<?php
use PHPUnit\Framework\TestCase;

include_once(dirname(__FILE__).'/../LightORM.php');
include_once('DatabaseLoader.php');

class Books extends LightORM {
    protected static $table = 'entries';
    protected static $primaryKey = 'isbn';
}

class BooksTest extends TestCase
{
    public static function setUpBeforeClass() {
        DatabaseLoader::setUp();
        DatabaseLoader::query('CREATE TABLE IF NOT EXISTS entries (
            isbn INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            author VARCHAR(255)
        )');
        Books::establishConnection(DatabaseLoader::$connection);
    }

    public static function tearDownAfterClass()
    {
        DatabaseLoader::clear('entries');
    }

    public function setUp() {
        if (!isset($this->book)) {
            $this->book = Books::create([
                'name' => '1984',
                'author' => 'George Orwell'
            ]);
        }
    }

    public function testTableName() {
        $this->assertEquals(Books::getTableName(), 'entries');
    }

    public function testPK() {
        $this->assertEquals(Books::getPrimaryKey(), 'isbn');
    }

    public function testCreateBook() {
        $this->assertInstanceOf('Books', $this->book);
    }

    public function testFindByNonstandardPK() {
        $found = Books::find($this->book->isbn);
        $this->assertEquals($this->book->reload(), $found);
    }

    public function testUpdateSQLinjection() {
        $this->book->name = "1'; TRUNCATE TABLE entries;";
        $result = $this->book->save();
        $book = Books::find($this->book->isbn);
        $this->assertTrue($result);
        // table wasn't truncated?
        $this->assertInstanceOf('Books', $book);
    }

    /**
     * @expectedException Exception
     */
    public function testFindSQLinjection() {
        $id = "test; TRUNCATE TABLE entries;";
        Books::find($id);
        $this->expectException(Exception::class);
        // table wasn't truncated?
        $this->assertTrue($this->book->save());
    }
}
