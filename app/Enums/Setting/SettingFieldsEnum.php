<?php

namespace App\Enums\Setting;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum SettingFieldsEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case DECIMAL_POINT   = 'decimal_point';
    case DISCOUNT        = 'discount';
    case TAX             = 'tax';
    case BUSINESS_NAME   = 'business_name';
    case LEGAL_NAME      = 'legal_name';
    case TAX_ID          = 'tax_id';
    case ADDRESS         = 'address';
    case PHONE           = 'phone';
    case EMAIL           = 'email';
    case LOGO_PATH       = 'logo_path';
    case CURRENCY_CODE   = 'currency_code';
    case CURRENCY_SYMBOL = 'currency_symbol';
    case TIMEZONE        = 'timezone';
    case DATE_FORMAT     = 'date_format';
    case TIME_FORMAT     = 'time_format';
    case RECEIPT_FOOTER  = 'receipt_footer';
    case THERMAL_PAPER_WIDTH = 'thermal_paper_width';
    case THERMAL_SHOW_LOGO = 'thermal_show_logo';
    case THERMAL_SHOW_CUSTOMER = 'thermal_show_customer';
    case THERMAL_SHOW_PAYMENT_REFS = 'thermal_show_payment_refs';
    case PRINT_COPIES = 'print_copies';
    case AUTO_OPEN_PRINT_DIALOG = 'auto_open_print_dialog';
    case RETURN_TO_POS_AFTER_PRINT = 'return_to_pos_after_print';
    case KEEP_SALE_CONFIRMATION = 'keep_sale_confirmation';
    case DEFAULT_SALE_DOCUMENT = 'default_sale_document';
    case CASH_REGISTER_NAME = 'cash_register_name';

    public static function labels(): array
    {
        return [
            self::DECIMAL_POINT->value   => "Decimal Point",
            self::DISCOUNT->value        => "Discount",
            self::TAX->value             => "Impuesto informativo",
            self::BUSINESS_NAME->value   => "Nombre comercial",
            self::LEGAL_NAME->value      => "Razón social",
            self::TAX_ID->value          => "RUC",
            self::ADDRESS->value         => "Dirección",
            self::PHONE->value           => "Teléfono",
            self::EMAIL->value           => "Correo",
            self::LOGO_PATH->value       => "Logo",
            self::CURRENCY_CODE->value   => "Moneda",
            self::CURRENCY_SYMBOL->value => "Símbolo monetario",
            self::TIMEZONE->value        => "Zona horaria",
            self::DATE_FORMAT->value     => "Formato de fecha",
            self::TIME_FORMAT->value     => "Formato de hora",
            self::RECEIPT_FOOTER->value  => "Mensaje al pie",
            self::THERMAL_PAPER_WIDTH->value => "Ancho térmico",
            self::THERMAL_SHOW_LOGO->value => "Mostrar logo",
            self::THERMAL_SHOW_CUSTOMER->value => "Mostrar cliente",
            self::THERMAL_SHOW_PAYMENT_REFS->value => "Mostrar referencias de pago",
            self::PRINT_COPIES->value => "Copias sugeridas",
            self::AUTO_OPEN_PRINT_DIALOG->value => "Abrir impresión automáticamente",
            self::RETURN_TO_POS_AFTER_PRINT->value => "Volver al POS después de imprimir",
            self::KEEP_SALE_CONFIRMATION->value => "Mantener confirmación postventa",
            self::DEFAULT_SALE_DOCUMENT->value => "Documento predeterminado",
            self::CASH_REGISTER_NAME->value => "Nombre de caja",
        ];
    }

    public static function defaults(): array
    {
        return [
            self::BUSINESS_NAME->value => 'LaraTory',
            self::LEGAL_NAME->value => null,
            self::TAX_ID->value => null,
            self::ADDRESS->value => null,
            self::PHONE->value => null,
            self::EMAIL->value => null,
            self::LOGO_PATH->value => null,
            self::CURRENCY_CODE->value => 'PEN',
            self::CURRENCY_SYMBOL->value => 'S/',
            self::TIMEZONE->value => 'America/Lima',
            self::DATE_FORMAT->value => 'd/m/Y',
            self::TIME_FORMAT->value => 'H:i',
            self::RECEIPT_FOOTER->value => 'Gracias por su compra.',
            self::THERMAL_PAPER_WIDTH->value => 80,
            self::THERMAL_SHOW_LOGO->value => true,
            self::THERMAL_SHOW_CUSTOMER->value => true,
            self::THERMAL_SHOW_PAYMENT_REFS->value => true,
            self::PRINT_COPIES->value => 1,
            self::AUTO_OPEN_PRINT_DIALOG->value => false,
            self::RETURN_TO_POS_AFTER_PRINT->value => false,
            self::KEEP_SALE_CONFIRMATION->value => true,
            self::DEFAULT_SALE_DOCUMENT->value => 'receipt',
            self::CASH_REGISTER_NAME->value => 'Caja',
            self::DECIMAL_POINT->value => 2,
            self::DISCOUNT->value => 0,
            self::TAX->value => 0,
        ];
    }

    public static function publicKeys(): array
    {
        return [
            self::BUSINESS_NAME->value,
            self::LEGAL_NAME->value,
            self::TAX_ID->value,
            self::ADDRESS->value,
            self::PHONE->value,
            self::EMAIL->value,
            self::CURRENCY_CODE->value,
            self::CURRENCY_SYMBOL->value,
            self::TIMEZONE->value,
            self::DATE_FORMAT->value,
            self::TIME_FORMAT->value,
            self::RECEIPT_FOOTER->value,
            self::THERMAL_PAPER_WIDTH->value,
            self::THERMAL_SHOW_LOGO->value,
            self::THERMAL_SHOW_CUSTOMER->value,
            self::THERMAL_SHOW_PAYMENT_REFS->value,
            self::PRINT_COPIES->value,
            self::AUTO_OPEN_PRINT_DIALOG->value,
            self::RETURN_TO_POS_AFTER_PRINT->value,
            self::KEEP_SALE_CONFIRMATION->value,
            self::DEFAULT_SALE_DOCUMENT->value,
            self::CASH_REGISTER_NAME->value,
            self::DECIMAL_POINT->value,
            self::DISCOUNT->value,
            self::TAX->value,
        ];
    }
}
