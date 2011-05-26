<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 AOE media GmbH <dev@aoemedia.de>
*  			
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once PATH_tx_update_refindex . 'Classes/Typo3/RefIndex.php';

/**
 * Tests class Tx_UpdateRefindex_Typo3_RefIndex
 * 
 * @package update_refindex
 * @subpackage Tests
 */
class Tx_UpdateRefindex_Typo3_RefIndexTest extends tx_phpunit_testcase {
	/**
	 * @var Tx_UpdateRefindex_Typo3_RefIndex
	 */
	private $refIndex;
	/**
	 * @var t3lib_refindex
	 */
	private $t3libRefindex;
	/**
	 * @var t3lib_DB
	 */
	private $typo3Db;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		$this->t3libRefindex = $this->getMock( 't3lib_refindex', array(), array(), '', false );
		$this->typo3Db = $this->getMock( 't3lib_DB', array(), array(), '', false );
		$this->refIndex = $this->getMock( 'Tx_UpdateRefindex_Typo3_RefIndex', array('createT3libRefindex','getExistingTables','getTypo3Db'));
		$this->refIndex->expects ( $this->any () )->method ( 'createT3libRefindex' )->will ( $this->returnValue ( $this->t3libRefindex ) );
		$this->refIndex->expects ( $this->any () )->method ( 'getTypo3Db' )->will ( $this->returnValue ( $this->typo3Db ) );
	}
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		unset( $this->refIndex );
		unset( $this->t3libRefindex );
		unset( $this->typo3Db );
	}

	/**
	 * Test method update
	 * @test
	 */
	public function update() {
		$selectedTables = array('tablename1','tablename2');
		$recordsOfTable1 = array( array('uid' => 10), array('uid' => 20) );
		$recordsOfTable2 = array( array('uid' => 70), array('uid' => 80), array('uid' => 90) );

		/**
		 * define behaviour of object refIndex
		 */
		$this->refIndex->expects ( $this->any () )->method ( 'getExistingTables' )->will ( $this->returnValue ( $selectedTables ) );

		/**
		 * define behaviour of object typo3Db
		 */
		// 1.1. find all records of table1
		$this->typo3Db->expects ( $this->at(0) )->method ( 'exec_SELECTgetRows' )->with('uid',$selectedTables[0],'1=1')->will ( $this->returnValue ( $recordsOfTable1 ) );
		// 1.2. Searching lost indexes of table1
		$this->typo3Db->expects ( $this->at(1) )->method ( 'fullQuoteStr' )->with($selectedTables[0],'sys_refindex')->will ( $this->returnValue ( $selectedTables[0] ) );
		$this->typo3Db->expects ( $this->at(2) )->method ( 'exec_DELETEquery' )->with('sys_refindex', 'tablename='.$selectedTables[0].' AND recuid NOT IN (0,'.$recordsOfTable1[0]['uid'].','.$recordsOfTable1[1]['uid'].')');
		// 2.1. find all records of table2
		$this->typo3Db->expects ( $this->at(3) )->method ( 'exec_SELECTgetRows' )->with('uid',$selectedTables[1],'1=1')->will ( $this->returnValue ( $recordsOfTable2 ) );
		// 2.2. Searching lost indexes of table2
		$this->typo3Db->expects ( $this->at(4) )->method ( 'fullQuoteStr' )->with($selectedTables[1],'sys_refindex')->will ( $this->returnValue ( $selectedTables[1] ) );
		$this->typo3Db->expects ( $this->at(5) )->method ( 'exec_DELETEquery' )->with('sys_refindex', 'tablename='.$selectedTables[1].' AND recuid NOT IN (0,'.$recordsOfTable2[0]['uid'].','.$recordsOfTable2[1]['uid'].','.$recordsOfTable2[2]['uid'].')');

		/**
		 * define behaviour of object t3libRefindex
		 */
		$this->t3libRefindex->expects ( $this->at(0) )->method ( 'updateRefIndexTable' )->with($selectedTables[0], $recordsOfTable1[0]['uid'], FALSE);
		$this->t3libRefindex->expects ( $this->at(1) )->method ( 'updateRefIndexTable' )->with($selectedTables[0], $recordsOfTable1[1]['uid'], FALSE);
		$this->t3libRefindex->expects ( $this->at(2) )->method ( 'updateRefIndexTable' )->with($selectedTables[1], $recordsOfTable2[0]['uid'], FALSE);
		$this->t3libRefindex->expects ( $this->at(3) )->method ( 'updateRefIndexTable' )->with($selectedTables[1], $recordsOfTable2[1]['uid'], FALSE);
		$this->t3libRefindex->expects ( $this->at(4) )->method ( 'updateRefIndexTable' )->with($selectedTables[1], $recordsOfTable2[2]['uid'], FALSE);

		// do test
		$this->refIndex->setSelectedTables( $selectedTables );
		$this->refIndex->update();
	}
}