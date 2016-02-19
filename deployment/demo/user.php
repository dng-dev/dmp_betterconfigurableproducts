#!/usr/bin/php
<?php 

// see Packr
require_once dirname(__FILE__) . UserCreator::MAGEDIR . '/app/Mage.php';

/**
 * Create an admin user programmatically
 * @author christoph
 *
 * @property string $username
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string $password
 * @property boolean $isActive
 * @property int $roleId
 */
class UserCreator
{
    const MAGEDIR = '/../..';
    
    protected $_data = array();

    /**
     * Initialize user object.
     * @param array $data The user's properties.
     */
    public function __construct(array $data = array())
    {
        // Initialize with default data, if none are passed to constructor
        if (!count($data)) {
            $data = array(
                'username'  => 'demo',
                'firstname' => 'Demo',
                'lastname'  => 'User',
                'email'     => 'demo@der-modulprogrammierer.de',
                'password'  => 'demo',
                'isActive'  => true,
                'roleId'    => 2
            );
        }

        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function __set($key, $value) 
    {
        $allowed_keys = array(
            'username', 'firstname', 'lastname', 'email', 'password', 'isActive', 'roleId', 'id'
        );

        if (!in_array($key, $allowed_keys)) {
            throw new Exception("Invalid property '$key'.");
        }

        $this->_data[$key] = $value;
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }

        return null;
    }

    /**
     * Save user to database.
     * @param Mage_Admin_Model_User $user
     * @return UserCreator
     */
    public function createUser(Mage_Admin_Model_User $user)
    {
        $user
            ->setUsername($this->username)
            ->setFirstname($this->firstname)
            ->setLastname($this->lastname)
            ->setEmail($this->email)
            ->setPassword($this->password)
            ->setIsActive($this->isActive)
            ->save();

        $this->id = $user->getId();

        return $this;
    }
}

class PermissionsCreator
{
    protected $roleId;

    protected $resources = array(
        'admin',
        'admin/catalog',
        'admin/catalog/products',
//         'admin/catalog/product',
//         'admin/catalog/product/edit',
        'admin/customer',
        'admin/customer/group',
        'admin/customer/manage',
        'admin/sales',
        'admin/sales/order',
        'admin/sales/order/actions',
        'admin/sales/order/actions/cancel',
        'admin/sales/order/actions/capture',
        'admin/sales/order/actions/comment',
        'admin/sales/order/actions/creditmemo',
        'admin/sales/order/actions/edit',
        'admin/sales/order/actions/email',
        'admin/sales/order/actions/hold',
        'admin/sales/order/actions/invoice',
        'admin/sales/order/actions/reorder',
        'admin/sales/order/actions/review_payment',
        'admin/sales/order/actions/ship',
        'admin/sales/order/actions/unhold',
        'admin/sales/order/actions/view',
        'admin/sales/invoice',
        'admin/sales/shipment',
        'admin/system',
        'admin/system/config',
    );

    public function __construct(int $roleId)
    {
        $this->roleId = $roleId;
    }

    /**
     * add additional resources
     *
     * @param array $allowedPermissions
     * @return PermissionsCreator
     */
    public function addPermissions(array $allowedPermissions = array())
    {
        $permissionsFile = dirname(__FILE__) . UserCreator::MAGEDIR . DIRECTORY_SEPARATOR . 'added_permissions.txt';
        if (file_exists($permissionsFile)) {
            $allowedPermissions = array_merge(unserialize(file_get_contents($permissionsFile)), $allowedPermissions);
        }
        echo "apply permissions: " . implode(', ', $allowedPermissions) . PHP_EOL;
        /* add additional resources */
        $this->resources = array_merge($this->resources, $allowedPermissions);
        return $this;
    }

    /**
     * remove resources
     *
     * @param array $allowedPermissions
     * @return PermissionsCreator
     */
    public function removePermissions(array $deniedPermissions = array())
    {
        $permissionsFile = dirname(__FILE__) . UserCreator::MAGEDIR . DIRECTORY_SEPARATOR . 'removed_permissions.txt';
        if (file_exists($permissionsFile)) {
            $deniedPermissions = array_merge(unserialize(file_get_contents($permissionsFile)), $deniedPermissions);
        }
        echo "remove permissions: " . implode(', ', $deniedPermissions) . PHP_EOL;
        /* strip denied resources */
        $this->resources = array_filter(
            $this->resources,
            function($item) { return false == in_array($item, $deniedPermissions); }
        );
        return $this;
    }

    /**
     * save rule
     *
     * @return PermissionsCreator
     */
    public function save()
    {
        $rule = Mage::getModel('admin/rules')
            ->setResources($this->resources)
            ->setRoleId($this->roleId)
            ->saveRel()
            ->save();
        return $this;
    }
}

class RoleCreator
{
    protected $name;

    protected $lastId = 0;
    protected $parentId = 0;
    protected $userId;
    protected $type;

    /**
     * set role name
     * 
     * @param string $name 
     * @return RoleCreator
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function save()
    {
        $type = $this->type;
        $role = Mage::getModel('admin/role')
            ->setRoleName($this->name)
            ->setUserId($this->userId)
            ->setRoleType($type)
            ->setTreeLevel($type=='G' ? 1 : 2)
            ->setParentId($this->parentId)
            ->save();
        $this->lastId = $role->getId();
        return $this;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function getLastId()
    {
        return $this->lastId;
    }
}


$app = Mage::app();
Mage_Core_Model_Resource_Setup::applyAllUpdates();
Mage_Core_Model_Resource_Setup::applyAllDataUpdates();

try {
    // ====================== CREATE DEFAULT DEMO USER ====================== //
    $userCreator = new UserCreator(array(
        'username'  => 'demo',
        'firstname' => 'Demo',
        'lastname'  => 'User',
        'email'     => 'demo@der-modulprogrammierer.de',
        'password'  => 'demo',
        'isActive'  => true,
    ));
    $user = Mage::getModel('admin/user');
    $userCreator->createUser($user);
    echo sprintf('user #%s created.' . PHP_EOL, $user->getId());

    // ================= CREATE DEFAULT ROLES & PERMISSIONS ================= //
    
    $roleCreator = new RoleCreator();
    $role = $roleCreator->setName('Demo')
        ->setType('G')
        ->setUserId(0)
        ->save();
    $parentRoleId = $role->getLastId();
    echo sprintf('Created parent role #%s' . PHP_EOL, $parentRoleId);
    
    $permissionsCreator = new PermissionsCreator($parentRoleId);
    $permissionsCreator->addPermissions()->removePermissions()->save();
    echo sprintf('Created permissions for role #%s.' . PHP_EOL, $parentRoleId);
    
    $roleCreator = new RoleCreator();
    $role = $roleCreator->setName('Demo')
        ->setType('U')
        ->setUserId($user->getId())
        ->setParentId($parentRoleId)
        ->save();
    echo sprintf('Created user role #%s for parent role #%s.' . PHP_EOL, $role->getLastId(), $role->getParentId());
    
    // ====================== CREATE GERMAN DEMO USER ======================= //
    $userCreator->username = 'demo_de';
    $userCreator->email = 'demo_de@der-modulprogrammierer.de';
    $user_de = Mage::getModel('admin/user');
    $userCreator->createUser($user_de);
    echo sprintf('user #%s created.' . PHP_EOL, $user_de->getId());
    
    // =================== APPLY ROLE TO GERMAN DEMO USER =================== //
    $role = $roleCreator->setUserId($user_de->getId())->save();
    echo sprintf('Created user role #%s for parent role #%s.' . PHP_EOL, $role->getLastId(), $role->getParentId());
    
} catch (Exception $ex) {
    $msg = "An error occured while creating user:\n";
    $msg.= $ex->getMessage() . ' (' . $ex->getFile() . ' l. ' . $ex->getLine() . ")\n";
    print($msg);
    exit(1);
}

?>
