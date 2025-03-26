<?php 

namespace Utils\PDF\Invoice;

class CustomInvoice
{
    /**
     * Stores the general settings array retrieved from the WooCommerce PDF Invoices & Packing Slips plugin.
     *
     * @var array|null $wpo Contains WooCommerce PDF Invoice settings or null if the option is not found.
     */
    public $wpo;

    /**
     * Constructor method to initialize the class and retrieve WooCommerce PDF Invoice settings.
     */
    public function __construct()
    {
        $this->wpo = get_option('wpo_wcpdf_settings_general');
    }

    /**
     * Retrieve a specific template part setting from the WooCommerce PDF Invoice settings.
     *
     * @param string $setting The key name of the setting to retrieve.
     * @return mixed The setting value, formatted with line breaks if found, or null if not found.
     */
    public function getTemplatePart($setting = ""): mixed 
    {
        if (is_array($this->wpo)) 
        {
            foreach ($this->wpo as $key => $value) {
                if ($setting === $key) {
                    return nl2br(esc_html($value["default"] ?? $value));
                }
            }
        }

        return null;
    }

    /**
     * Display the header logo in HTML format. 
     * If a logo is set in the WooCommerce PDF Invoice settings, it outputs the logo with a custom height.
     * If not, it attempts to display the shop title.
     */
    public function displayHeaderLogo() 
    {
        if ($this->getTemplatePart("header_logo")) {
            echo '<img style="height: ' . esc_attr($this->getTemplatePart("header_logo_height")) . ';" src="' . esc_attr($this->getHeaderLogo()) . '" alt="Shop Logo">';
        } else {
            $this->wpo->title();
        }
    }

    /**
     * Retrieve the header logo as a base64-encoded image string.
     *
     * @param int|null $logo_id Optional. The attachment ID of the logo. If null, it uses the ID from the WooCommerce PDF Invoice settings.
     * @return string Base64-encoded image string with MIME type, or an empty string if the file cannot be retrieved.
     */
    private function getHeaderLogo($logo_id = null)
    {
        $logo_id = $logo_id ? $logo_id : $this->getTemplatePart("header_logo");
        $logo_path = get_attached_file($logo_id);

        $logo_mime = mime_content_type($logo_path); 
        $logo_data = base64_encode(file_get_contents($logo_path));
        return 'data:' . $logo_mime . ';base64,' . $logo_data;
    }
}
