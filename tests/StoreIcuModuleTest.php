<?php
/**
 * Store.icu WHMCS Module Test
 *
 * PHPUnit test for the Store.icu module.
 */
class StoreIcuModuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var string $moduleName */
    protected $moduleName = 'store_icu';

    /**
     * Asserts the required config options function is defined.
     */
    public function testRequiredConfigOptionsFunctionExists()
    {
        $this->assertTrue(function_exists($this->moduleName . '_ConfigOptions'));
    }

    /**
     * Data provider of module function return data types.
     *
     * Used in verifying module functions return data of the correct type.
     *
     * @return array
     */
    public function providerFunctionReturnTypes(): array
    {
        return array(
            'Config Options' => array('ConfigOptions', 'array'),
            'Meta Data' => array('MetaData', 'array'),
            'Create' => array('CreateAccount', 'string'),
            'Suspend' => array('SuspendAccount', 'string'),
            'Unsuspend' => array('UnsuspendAccount', 'string'),
            'Terminate' => array('TerminateAccount', 'string'),
            'Change Package' => array('ChangePackage', 'string'),
            'Admin Services Tab Fields' => array('AdminServicesTabFields', 'array'),
            'Service Single Sign-On' => array('ServiceSingleSignOn', 'array'),
            'Client Area Output' => array('ClientArea', 'array'),
        );
    }

    /**
     * Test module functions return appropriate data types.
     *
     * @param string $function
     * @param string $returnType
     *
     * @dataProvider providerFunctionReturnTypes
     */
    public function testFunctionsReturnAppropriateDataType(string $function, string $returnType)
    {
        if (function_exists($this->moduleName . '_' . $function)) {
            $result = call_user_func($this->moduleName . '_' . $function, array());
            if ($returnType == 'array') {
                $this->assertTrue(is_array($result));
            } elseif ($returnType == 'null') {
                $this->assertTrue(is_null($result));
            } else {
                $this->assertTrue(is_string($result));
            }
        }
    }
    
    /**
     * Test that the API call function performs proper error handling
     */
    public function testApiCallHandlesErrors()
    {
        // Mock an invalid API endpoint call
        $params = array();
        $endpoint = 'invalid-endpoint';
        $result = store_icu_ApiCall($params, $endpoint);
        
        // Should return an array with success => false
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }
    
    /**
     * Test that authentication requires proper credentials
     */
    public function testAuthenticationRequiresCredentials()
    {
        // Call authenticate with empty credentials
        $params = array(
            'configoption1' => '',
            'configoption2' => '',
        );
        
        $result = store_icu_Authenticate($params);
        
        // Should fail with missing credentials
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }
}
