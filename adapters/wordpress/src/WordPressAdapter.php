<?php
namespace AGNSTK\Adapters\WordPress;

use AGNSTK\Core\Services\MembershipService;

class WordPressAdapter {
    
    protected $membershipService;
    
    public function __construct() {
        $this->membershipService = new MembershipService();
    }
    
    public function getMembership() {
        $userId = $this->getCurrentUserId();
        $membership = $this->membershipService->getUserMembership($userId);
        
        return "<div class='agnstk-membership'><h3>AGNSTK Example App</h3><p>WordPress Plugin Version</p><p>$membership</p></div>";
    }
    
    protected function getCurrentUserId() {
        // Use WordPress function if available, fallback to 1 for testing
        if (function_exists('get_current_user_id')) {
            return get_current_user_id() ?: 1;
        }
        return 1;
    }
}
