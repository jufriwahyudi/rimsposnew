<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use Illuminate\Database\Seeder;

class ProductOldSeeder extends Seeder
{
    public function run(): void
    {
        $seq = 1;

        $products = [
            [
                'kode_produk' => 'HANASUIBLUSHON',
                'nama_produk' => 'hanasui blush on',
                'variants'    => [
                    ['label' => '03', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'PIXYPRIMER',
                'nama_produk' => 'pixy primer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 47000],
                ],
            ],
            [
                'kode_produk' => 'OTWOOPRIMERZERO',
                'nama_produk' => 'o.two.o primer zero',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 63000],
                ],
            ],
            [
                'kode_produk' => 'WARDAHCONCEALERLIGHT',
                'nama_produk' => 'wardah concealer lightening',
                'variants'    => [
                    ['label' => '11C', 'harga_jual' => 42000],
                    ['label' => '32N', 'harga_jual' => 42000],
                ],
            ],
            [
                'kode_produk' => 'SALSAEYESHADOW',
                'nama_produk' => 'salsa eyeshadow',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'G2GBODYSERUM',
                'nama_produk' => 'G2G body serum',
                'variants'    => [
                    ['label' => 'niacinamide bright', 'harga_jual' => 45000],
                    ['label' => 'retinol bright', 'harga_jual' => 45000],
                    ['label' => 'tropikal Velvet Oren pum', 'harga_jual' => 65000],
                    ['label' => 'creamy berry pink pum', 'harga_jual' => 65000],
                    ['label' => 'creamy berry pink tube', 'harga_jual' => 47000],
                    ['label' => 'tropikal Velvet Oren tube', 'harga_jual' => 47000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWTREMELA',
                'nama_produk' => 'G2G fw tremela',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWBLUEBERRY',
                'nama_produk' => 'G2G fw blueberry',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWCENTELA',
                'nama_produk' => 'G2G fw centela',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWLOW',
                'nama_produk' => 'G2G fw Low',
                'variants'    => [
                    ['label' => 'PH', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWNICINAMIDE',
                'nama_produk' => 'G2G fw nicinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 39000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWMILK',
                'nama_produk' => 'G2G fw milk',
                'variants'    => [
                    ['label' => 'amino pum', 'harga_jual' => 42000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWVITA',
                'nama_produk' => 'G2G fw Vita',
                'variants'    => [
                    ['label' => 'c pum', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'G2GFWMATCHA',
                'nama_produk' => 'G2G fw matcha',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'G2GTONERGLYCOLIC',
                'nama_produk' => 'G2G toner glycolic',
                'variants'    => [
                    ['label' => 'acid 7%', 'harga_jual' => 47000],
                ],
            ],
            [
                'kode_produk' => 'G2GTONERPROPOLIS',
                'nama_produk' => 'G2G toner propolis',
                'variants'    => [
                    ['label' => 'kuning', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'G2GTONERBLACBERRY',
                'nama_produk' => 'G2G toner blacberry',
                'variants'    => [
                    ['label' => 'ungu', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'G2GTONERPROMEGRANATE',
                'nama_produk' => 'G2G toner promegranate',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 44000],
                ],
            ],
            [
                'kode_produk' => 'G2GSERUMPROMEGRANATE',
                'nama_produk' => 'G2G serum promegranate',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'G2GSERUMJEJU',
                'nama_produk' => 'G2G serum jeju',
                'variants'    => [
                    ['label' => 'tangerine kuning', 'harga_jual' => 48000],
                ],
            ],
            [
                'kode_produk' => 'G2GSERUMCENTELA',
                'nama_produk' => 'G2G serum centela',
                'variants'    => [
                    ['label' => 'hijau', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'G2GSERUMDARK',
                'nama_produk' => 'G2G serum dark',
                'variants'    => [
                    ['label' => 'spot', 'harga_jual' => 42000],
                ],
            ],
            [
                'kode_produk' => 'G2GSERUMRETINOL',
                'nama_produk' => 'G2G serum retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 48000],
                ],
            ],
            [
                'kode_produk' => 'G2GSERUMINTENSIVE',
                'nama_produk' => 'G2G serum intensive',
                'variants'    => [
                    ['label' => 'peeling', 'harga_jual' => 48000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISPINK',
                'nama_produk' => 'G2G mois pink',
                'variants'    => [
                    ['label' => '100gr pum', 'harga_jual' => 82000],
                    ['label' => '100gr jar', 'harga_jual' => 90000],
                    ['label' => '30gr jar', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISPUTIH',
                'nama_produk' => 'G2G mois putih',
                'variants'    => [
                    ['label' => '100gr jar', 'harga_jual' => 100000],
                    ['label' => '30gr jar', 'harga_jual' => 47000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISCENTELA',
                'nama_produk' => 'G2G mois centela',
                'variants'    => [
                    ['label' => '30gr', 'harga_jual' => 39000],
                    ['label' => '55gr', 'harga_jual' => 47000],
                    ['label' => '100gr pum', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISBLUEBERRY',
                'nama_produk' => 'G2G mois blueberry',
                'variants'    => [
                    ['label' => 'ungu 30gr', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISVITA',
                'nama_produk' => 'G2G mois Vita',
                'variants'    => [
                    ['label' => 'c kuning 30gr', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISTREMELLA',
                'nama_produk' => 'G2G mois tremella',
                'variants'    => [
                    ['label' => 'Vita B5 30gr', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISKIWI',
                'nama_produk' => 'G2G mois kiwi',
                'variants'    => [
                    ['label' => '3D acid', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'G2GMOISRETINOL',
                'nama_produk' => 'G2G mois retinol',
                'variants'    => [
                    ['label' => '30gr', 'harga_jual' => 47000],
                ],
            ],
            [
                'kode_produk' => 'G2GMWCHERRY',
                'nama_produk' => 'G2G M.W Cherry',
                'variants'    => [
                    ['label' => 'blossom 300ml', 'harga_jual' => 47000],
                    ['label' => 'blosom all in one 130ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'G2GMWBRITENING',
                'nama_produk' => 'G2G M.W britening',
                'variants'    => [
                    ['label' => 'all in one 300ml', 'harga_jual' => 47000],
                ],
            ],
            [
                'kode_produk' => 'G2GMWPORE',
                'nama_produk' => 'G2G M.W pore',
                'variants'    => [
                    ['label' => 'clearing all in one 300ml', 'harga_jual' => 47000],
                    ['label' => 'clearing all in one 130 ml', 'harga_jual' => 28000],
                    ['label' => 'britening all in one 130ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'G2GMWTREMELLA',
                'nama_produk' => 'G2G M.W tremella',
                'variants'    => [
                    ['label' => 'panthenol 300ml', 'harga_jual' => 47000],
                    ['label' => 'panthenol 130 ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'G2GMWVITA',
                'nama_produk' => 'G2G M.W Vita',
                'variants'    => [
                    ['label' => 'C 300ml', 'harga_jual' => 47000],
                    ['label' => 'C 130 ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'G2GMWMUGWORD',
                'nama_produk' => 'G2G M.W mugword',
                'variants'    => [
                    ['label' => 'hijau 300ml', 'harga_jual' => 47000],
                    ['label' => 'hijau 130 ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'G2GREMOVERPENTHANOL',
                'nama_produk' => 'G2G remover penthanol',
                'variants'    => [
                    ['label' => '150ml', 'harga_jual' => 47000],
                    ['label' => '60ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'G2GCLEANSINGOIL',
                'nama_produk' => 'G2G cleansing oil',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 56000],
                    ['label' => '200 ml', 'harga_jual' => 90000],
                ],
            ],
            [
                'kode_produk' => 'G2GACNEPATCH',
                'nama_produk' => 'G2G acne patch',
                'variants'    => [
                    ['label' => 'nigh', 'harga_jual' => 27000],
                    ['label' => 'day', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'G2GACNEDRIYING',
                'nama_produk' => 'G2G acne driying',
                'variants'    => [
                    ['label' => 'lotion totol jerawat', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'G2GMUGWORTACNE',
                'nama_produk' => 'G2G mugwort acne',
                'variants'    => [
                    ['label' => 'gel mask', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'G2GVOLCANOCLAY',
                'nama_produk' => 'G2G volcano clay',
                'variants'    => [
                    ['label' => 'mask 30gr', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'G2GCLAYSTICK',
                'nama_produk' => 'G2G clay stick',
                'variants'    => [
                    ['label' => 'volcano 25gr', 'harga_jual' => 43000],
                    ['label' => 'pomegranate 25gr', 'harga_jual' => 43000],
                    ['label' => 'acne 25gr', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'G2GBRIGHTUP',
                'nama_produk' => 'G2G bright up',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'G2GDOBLEBRIGHT',
                'nama_produk' => 'G2G Doble bright',
                'variants'    => [
                    ['label' => 'day cream', 'harga_jual' => 72000],
                ],
            ],
            [
                'kode_produk' => 'G2GLIPSERUM',
                'nama_produk' => 'G2G lip serum',
                'variants'    => [
                    ['label' => 'clear', 'harga_jual' => 56000],
                    ['label' => 'berry', 'harga_jual' => 56000],
                    ['label' => 'pink', 'harga_jual' => 55000],
                    ['label' => 'peach', 'harga_jual' => 55000],
                    ['label' => 'mixberry', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'G2GSERUMSPREY',
                'nama_produk' => 'G2G serum sprey',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 75000],
                ],
            ],
            [
                'kode_produk' => 'G2GSETTINGSPREY',
                'nama_produk' => 'G2G setting sprey',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'G2GCUSHION2',
                'nama_produk' => 'G2G cushion 2',
                'variants'    => [
                    ['label' => 'in 1 01', 'harga_jual' => 160000],
                    ['label' => 'in 1 02', 'harga_jual' => 160000],
                ],
            ],
            [
                'kode_produk' => 'G2GPOWDERFONDATION',
                'nama_produk' => 'G2G powder fondation',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 80000],
                    ['label' => '01', 'harga_jual' => 80000],
                    ['label' => '02', 'harga_jual' => 80000],
                    ['label' => '03', 'harga_jual' => 80000],
                    ['label' => '04', 'harga_jual' => 80000],
                ],
            ],
            [
                'kode_produk' => 'G2GCUSHIONPINK',
                'nama_produk' => 'G2G cushion pink',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 89000],
                    ['label' => '01', 'harga_jual' => 89000],
                    ['label' => '03', 'harga_jual' => 89000],
                    ['label' => '04', 'harga_jual' => 89000],
                ],
            ],
            [
                'kode_produk' => 'G2GCUSHIONREFIL',
                'nama_produk' => 'G2G cushion refil',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 72000],
                    ['label' => '01', 'harga_jual' => 72000],
                    ['label' => '02', 'harga_jual' => 72000],
                    ['label' => '03', 'harga_jual' => 72000],
                ],
            ],
            [
                'kode_produk' => 'G2GCUSHIONSILVER',
                'nama_produk' => 'G2G cushion silver',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 90000],
                    ['label' => '02', 'harga_jual' => 90000],
                    ['label' => '03', 'harga_jual' => 90000],
                    ['label' => '04', 'harga_jual' => 90000],
                ],
            ],
            [
                'kode_produk' => 'G2GSKINTNT01',
                'nama_produk' => 'G2G skintnt 01',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'G2GSKINTNT02',
                'nama_produk' => 'G2G skintnt 02',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55555],
                ],
            ],
            [
                'kode_produk' => 'G2GSKINTNT03',
                'nama_produk' => 'G2G skintnt 03',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'G2GSKINTNT04',
                'nama_produk' => 'G2G skintnt 04',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKFINISINGPOWDE',
                'nama_produk' => 'glamfik finising powder',
                'variants'    => [
                    ['label' => 'puff', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKLOOSEPOWDER',
                'nama_produk' => 'glamfik loose powder',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 18000],
                    ['label' => 'puff kuning', 'harga_jual' => 17000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKWET',
                'nama_produk' => 'glamfik wet &',
                'variants'    => [
                    ['label' => 'dry powder puff bulat', 'harga_jual' => 15000],
                    ['label' => 'dry powder puff segi empat', 'harga_jual' => 15000],
                    ['label' => 'dry powder powder puff campur isi dua', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKEXELLENTBRUSH',
                'nama_produk' => 'glamfik exellent brush',
                'variants'    => [
                    ['label' => 'set pink', 'harga_jual' => 35000],
                    ['label' => 'set hijau', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKEYEBROWTRIMME',
                'nama_produk' => 'glamfik eyebrow trimmer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 17000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKEYEBLENDING',
                'nama_produk' => 'glamfik eye blending',
                'variants'    => [
                    ['label' => 'brush', 'harga_jual' => 17000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKFLAWLESSEYESH',
                'nama_produk' => 'glamfik flawless eyeshadow',
                'variants'    => [
                    ['label' => 'blanding brush', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKBLUSHERBRUSH',
                'nama_produk' => 'glamfik blusher brush',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 37000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKPRECISIONEYES',
                'nama_produk' => 'glamfik precision eyeshadow',
                'variants'    => [
                    ['label' => 'brush', 'harga_jual' => 17000],
                ],
            ],
            [
                'kode_produk' => 'GLAMFIKEYEBROWBRUSH',
                'nama_produk' => 'glamfik eyebrow brush',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'SALSABLUSHON',
                'nama_produk' => 'salsa blush on',
                'variants'    => [
                    ['label' => '02', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'SALSAHIGHLIGHTER02',
                'nama_produk' => 'salsa highlighter 02',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'SALSAHIGHLIGHTER03',
                'nama_produk' => 'salsa highlighter 03',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'SALSAHIGHLIGHTER01',
                'nama_produk' => 'salsa highlighter 01',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'JUSTMISSWONDER',
                'nama_produk' => 'just miss wonder',
                'variants'    => [
                    ['label' => 'pallate 05 ia ule', 'harga_jual' => 25000],
                    ['label' => 'pallate 06 fly me', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'SANIYENUDE02',
                'nama_produk' => 'saniye nude 02',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'MADAMGIETOGO',
                'nama_produk' => 'Madamgie to go',
                'variants'    => [
                    ['label' => 'eyeshadow', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'FANBOBROWCARA2',
                'nama_produk' => 'fanbo browcara 2',
                'variants'    => [
                    ['label' => 'in 1 natural', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'FOCALURPOMADE05',
                'nama_produk' => 'focalur Pomade 05',
                'variants'    => [
                    ['label' => 'ebony', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'FOCALURPOMADE01',
                'nama_produk' => 'focalur Pomade 01',
                'variants'    => [
                    ['label' => 'auburn', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORVITBLUSH',
                'nama_produk' => 'Wrdh colorvit blush',
                'variants'    => [
                    ['label' => '05', 'harga_jual' => 50000],
                    ['label' => '06', 'harga_jual' => 50000],
                    ['label' => '03', 'harga_jual' => 50000],
                    ['label' => '01', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'FOCALURGLOW',
                'nama_produk' => 'Focalur glow &',
                'variants'    => [
                    ['label' => 'contour', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'SANIYEEYESHADOW',
                'nama_produk' => 'saniye eyeshadow',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'BRASOVBLUSHON',
                'nama_produk' => 'Brasov blush on',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 18000],
                ],
            ],
            [
                'kode_produk' => 'SANIYEEYESOF',
                'nama_produk' => 'saniye eyes of',
                'variants'    => [
                    ['label' => 'encantment 03', 'harga_jual' => 40000],
                    ['label' => 'encantment 02', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'SANIYE3COLORS',
                'nama_produk' => 'saniye 3 colors',
                'variants'    => [
                    ['label' => 'face powder pallet 01', 'harga_jual' => 30000],
                    ['label' => 'face powder pallet 02', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKPUREVIT',
                'nama_produk' => 'skintifik pure VIT',
                'variants'    => [
                    ['label' => 'c 1+1', 'harga_jual' => 130000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKMOISCERAMID',
                'nama_produk' => 'skintifik mois ceramide',
                'variants'    => [
                    ['label' => 'light texture', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSERUM10',
                'nama_produk' => 'skintifik serum 10%',
                'variants'    => [
                    ['label' => 'niacinamide', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKMOISNIACINA',
                'nama_produk' => 'skintifik mois niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKMOISRETINOL',
                'nama_produk' => 'skintifik mois retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 130000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSERUMRETINO',
                'nama_produk' => 'skintifik serum retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKMOIS377',
                'nama_produk' => 'skintifik mois 377',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSERUM377',
                'nama_produk' => 'skintifik serum 377',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 130000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSETTINGSPRE',
                'nama_produk' => 'skintifik setting sprey',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 105000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKMWNIACINAMI',
                'nama_produk' => 'skintifik M.W niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKMWCERAMIDE',
                'nama_produk' => 'skintifik M.W ceramide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKCLEANSINGOI',
                'nama_produk' => 'skintifik cleansing oil',
                'variants'    => [
                    ['label' => 'centela', 'harga_jual' => 100000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSUNSPREY',
                'nama_produk' => 'skintifik sun sprey',
                'variants'    => [
                    ['label' => '120ml', 'harga_jual' => 95000],
                    ['label' => '70ml', 'harga_jual' => 75000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSUN5X',
                'nama_produk' => 'skintifik sun 5x',
                'variants'    => [
                    ['label' => 'ceramide', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSUNBRIGHT',
                'nama_produk' => 'skintifik sun bright',
                'variants'    => [
                    ['label' => 'fit pink', 'harga_jual' => 120000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSUNMATTE',
                'nama_produk' => 'skintifik sun matte',
                'variants'    => [
                    ['label' => 'fit ungu', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKFWNIACINAMI',
                'nama_produk' => 'skintifik FW niacinamide',
                'variants'    => [
                    ['label' => '80ml', 'harga_jual' => 95000],
                    ['label' => '60ml', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKFW5X',
                'nama_produk' => 'skintifik FW 5x',
                'variants'    => [
                    ['label' => 'ceramide 80ml', 'harga_jual' => 95000],
                    ['label' => 'ceramide 60 ml', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKFWAMINO',
                'nama_produk' => 'skintifik FW amino',
                'variants'    => [
                    ['label' => 'acid 100ml', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKFWPANTHENOL',
                'nama_produk' => 'skintifik FW panthenol',
                'variants'    => [
                    ['label' => '80ml', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'SLAVINABODYLOTION',
                'nama_produk' => 'Slavina body lotion',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'SLAVINABODYWASH',
                'nama_produk' => 'Slavina body wash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'BIZAREBODYLOTION',
                'nama_produk' => 'Bizare body lotion',
                'variants'    => [
                    ['label' => 'young', 'harga_jual' => 30000],
                    ['label' => 'natural', 'harga_jual' => 30000],
                    ['label' => 'miracle', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'SCARLETBODYLOTION',
                'nama_produk' => 'Scarlet body lotion',
                'variants'    => [
                    ['label' => 'freshy', 'harga_jual' => 60000],
                    ['label' => 'romansa', 'harga_jual' => 60000],
                    ['label' => 'jolly', 'harga_jual' => 60000],
                    ['label' => 'charming', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'BODYWHITETONER',
                'nama_produk' => 'Body white toner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'BODYWHITEBODY',
                'nama_produk' => 'Body white body',
                'variants'    => [
                    ['label' => 'lotion + serum', 'harga_jual' => 40000],
                    ['label' => 'lotion spf 30 + serum', 'harga_jual' => 45000],
                ],
            ],
            [
                'kode_produk' => 'BODYWHITESERUM',
                'nama_produk' => 'Body white serum',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 35000],
                    ['label' => '30 ml', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'BODYWHITEEXFOLITING',
                'nama_produk' => 'Body white exfoliting',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'BODYWHITESABUN',
                'nama_produk' => 'Body white sabun',
                'variants'    => [
                    ['label' => 'mandi', 'harga_jual' => 37000],
                ],
            ],
            [
                'kode_produk' => 'ALOVERA98',
                'nama_produk' => 'Alovera 98%',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'VESSSICAMASKERPUTIH',
                'nama_produk' => 'Vesssica masker putih',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'VESSSICAMASKERHIJAU',
                'nama_produk' => 'Vesssica masker hijau',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'THEORIGINOTMOIS',
                'nama_produk' => 'The originot mois',
                'variants'    => [
                    ['label' => 'hyalucera', 'harga_jual' => 40000],
                    ['label' => 'cica B5', 'harga_jual' => 50000],
                    ['label' => 'britening', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'OMGMOIS2',
                'nama_produk' => 'OMG mois 2%',
                'variants'    => [
                    ['label' => 'cica', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'OMGMOIS5',
                'nama_produk' => 'OMG mois 5%',
                'variants'    => [
                    ['label' => 'niacinamide', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'SCORASERUMCICA',
                'nama_produk' => 'scora serum cica',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'SCORASERUMGLOW',
                'nama_produk' => 'scora serum glow',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'SCORASERUMBARIER',
                'nama_produk' => 'scora serum barier',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'SCORAMOISPANTHENOL',
                'nama_produk' => 'scora mois panthenol',
                'variants'    => [
                    ['label' => '40 ml', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'SCORAMOISNIACINAMIDE',
                'nama_produk' => 'scora mois niacinamide',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'GLOWSOPHYMOISBRIGH',
                'nama_produk' => 'Glowsophy mois brigh',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'GLOWSOPHYMOISCICA',
                'nama_produk' => 'Glowsophy mois cica',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'GLOWIESDREAMYMOISTUR',
                'nama_produk' => 'Glowies dreamy moisturizer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'TISHABODYSERUM',
                'nama_produk' => 'Tisha body serum',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 25000],
                    ['label' => 'hijau', 'harga_jual' => 25000],
                    ['label' => 'orange', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'NATUREBODY',
                'nama_produk' => 'Natur E body',
                'variants'    => [
                    ['label' => 'lotion pink 245 ml', 'harga_jual' => 30000],
                    ['label' => 'lotion pink 100 ml', 'harga_jual' => 17000],
                    ['label' => 'lotion hijau 100 ml', 'harga_jual' => 15000],
                    ['label' => 'lotion hijau 245 ml', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'ENCHANTEURBODYLOTION',
                'nama_produk' => 'Enchanteur body lotion',
                'variants'    => [
                    ['label' => 'kuning 100 ml', 'harga_jual' => 18000],
                    ['label' => 'pink 100 ml', 'harga_jual' => 18000],
                    ['label' => 'ungu 100 ml', 'harga_jual' => 18000],
                    ['label' => 'kuning 200 ml', 'harga_jual' => 30000],
                    ['label' => 'pink 200ml', 'harga_jual' => 30000],
                    ['label' => 'ungu 200 ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWRENEW',
                'nama_produk' => 'Wrdh FW renew',
                'variants'    => [
                    ['label' => 'you', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWHYDRA',
                'nama_produk' => 'Wrdh FW hydra',
                'variants'    => [
                    ['label' => 'rose pink 100 ml', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWCDEFENSE',
                'nama_produk' => 'Wrdh FW C-defense',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 35000],
                    ['label' => '50 ml', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWLIGHT',
                'nama_produk' => 'Wrdh FW light',
                'variants'    => [
                    ['label' => 'whip 100 ml', 'harga_jual' => 35000],
                    ['label' => 'gentle 100 ml', 'harga_jual' => 35000],
                    ['label' => 'gentle 50 ml', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWCRYSTAL',
                'nama_produk' => 'Wrdh FW crystal',
                'variants'    => [
                    ['label' => 'secreet', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWNATURE',
                'nama_produk' => 'Wrdh FW nature',
                'variants'    => [
                    ['label' => 'daily', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMILKCLENSER',
                'nama_produk' => 'Wrdh milk clenser',
                'variants'    => [
                    ['label' => 'crystal secreet', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWBRIGHT',
                'nama_produk' => 'Wrdh FW bright',
                'variants'    => [
                    ['label' => 'now glutation 100 ml', 'harga_jual' => 30000],
                    ['label' => 'vit c 100 ml', 'harga_jual' => 30000],
                    ['label' => 'vit c 50 ml', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWACNE',
                'nama_produk' => 'Wrdh FW acne',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 35000],
                    ['label' => '50 ml', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'WRDHFWALOE',
                'nama_produk' => 'Wrdh FW Aloe',
                'variants'    => [
                    ['label' => 'cica 100 ml', 'harga_jual' => 30000],
                    ['label' => 'cica 50 ml', 'harga_jual' => 20000],
                    ['label' => 'cooling bright 100 ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISALOE',
                'nama_produk' => 'Wrdh mois Aloe',
                'variants'    => [
                    ['label' => 'nature dayly 100 ml', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOIS14X',
                'nama_produk' => 'Wrdh mois 14x',
                'variants'    => [
                    ['label' => 'hyaluron + pentavitin 30 gr', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISCICA',
                'nama_produk' => 'Wrdh mois cica',
                'variants'    => [
                    ['label' => 'komplek+panthenol 30 gr', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISVIT',
                'nama_produk' => 'Wrdh mois VIT',
                'variants'    => [
                    ['label' => 'c+adenosine 30 gr', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'WRDHDAYLIGHT',
                'nama_produk' => 'Wrdh day light',
                'variants'    => [
                    ['label' => '30gr', 'harga_jual' => 33000],
                ],
            ],
            [
                'kode_produk' => 'WRDHNIGHRETINOL',
                'nama_produk' => 'Wrdh nigh retinol',
                'variants'    => [
                    ['label' => 'microkaps 30gr', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLIGHTFOR',
                'nama_produk' => 'Wrdh light for',
                'variants'    => [
                    ['label' => 'day 20 gr', 'harga_jual' => 33000],
                    ['label' => 'day & night 20 gr', 'harga_jual' => 33000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSERUM399',
                'nama_produk' => 'Wrdh serum 399',
                'variants'    => [
                    ['label' => '15 ml', 'harga_jual' => 40000],
                    ['label' => '30 ml', 'harga_jual' => 75000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISCRYSTAL',
                'nama_produk' => 'Wrdh mois crystal',
                'variants'    => [
                    ['label' => 'day secreet 30 gr', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISNIGH',
                'nama_produk' => 'Wrdh mois nigh',
                'variants'    => [
                    ['label' => 'crystal secreet 15 gr', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'WRDHDAYCRYSTAL',
                'nama_produk' => 'Wrdh day crystal',
                'variants'    => [
                    ['label' => 'secreet 15 gr', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSERUMCRYSTAL',
                'nama_produk' => 'Wrdh serum crystal',
                'variants'    => [
                    ['label' => 'secreet 20 ml', 'harga_jual' => 90000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISCDEFENCE',
                'nama_produk' => 'Wrdh mois c-defence',
                'variants'    => [
                    ['label' => '30 gr', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'WRDHDAYLIGHTENING',
                'nama_produk' => 'Wrdh day lightening',
                'variants'    => [
                    ['label' => '20 gr', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISBRIGHT',
                'nama_produk' => 'Wrdh mois bright',
                'variants'    => [
                    ['label' => 'oil control 20 ml', 'harga_jual' => 30000],
                    ['label' => 'smooth 20 ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISDAY',
                'nama_produk' => 'Wrdh mois day',
                'variants'    => [
                    ['label' => 'microcaps 30 gr', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOIS',
                'nama_produk' => 'Wrdh mois &',
                'variants'    => [
                    ['label' => 'day ceramide maxtryl 15 gr', 'harga_jual' => 50000],
                    ['label' => 'night retinol microcaps maxtryl 15 gr', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKCUSHIONGOLD',
                'nama_produk' => 'skintifik cushion gold',
                'variants'    => [
                    ['label' => '03 A', 'harga_jual' => 150000],
                    ['label' => '01', 'harga_jual' => 150000],
                    ['label' => '02', 'harga_jual' => 150000],
                    ['label' => '03', 'harga_jual' => 150000],
                    ['label' => '04', 'harga_jual' => 150000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKCUSHIONPINK',
                'nama_produk' => 'skintifik cushion pink',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 150000],
                    ['label' => '02', 'harga_jual' => 150000],
                    ['label' => '03', 'harga_jual' => 150000],
                    ['label' => '04', 'harga_jual' => 150000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKREFILCUSHIO',
                'nama_produk' => 'skintifik refil cushion',
                'variants'    => [
                    ['label' => 'silver 03', 'harga_jual' => 120000],
                    ['label' => 'silver 02', 'harga_jual' => 120000],
                    ['label' => 'silver 01', 'harga_jual' => 120000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKCUSHIONBIRU',
                'nama_produk' => 'skintifik cushion biru',
                'variants'    => [
                    ['label' => '00', 'harga_jual' => 150000],
                    ['label' => '01', 'harga_jual' => 150000],
                    ['label' => '02', 'harga_jual' => 150000],
                    ['label' => '03', 'harga_jual' => 150000],
                    ['label' => '04', 'harga_jual' => 150000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKCUSHIONREFL',
                'nama_produk' => 'skintifik cushion reflil',
                'variants'    => [
                    ['label' => 'biru 00', 'harga_jual' => 120000],
                    ['label' => 'biru 01', 'harga_jual' => 120000],
                    ['label' => 'biru 02', 'harga_jual' => 120000],
                    ['label' => 'biru 03', 'harga_jual' => 120000],
                    ['label' => 'biru 04', 'harga_jual' => 120000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKBEDAKPADAT',
                'nama_produk' => 'Skintifik bedak padat',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 135000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKSKINTINT',
                'nama_produk' => 'Skintifik skin tint',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 85000],
                ],
            ],
            [
                'kode_produk' => 'ESQACUSHIONGRANULA',
                'nama_produk' => 'Esqa cushion granula',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000],
                ],
            ],
            [
                'kode_produk' => 'ESQACUSHIONPANCAKE',
                'nama_produk' => 'Esqa cushion pancake',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000],
                ],
            ],
            [
                'kode_produk' => 'ESQACUSHIONCUSTARD',
                'nama_produk' => 'Esqa cushion custard',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000],
                ],
            ],
            [
                'kode_produk' => 'ESQACUSHIONMILKSHAKE',
                'nama_produk' => 'Esqa cushion milkshake',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 155000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCUSHIONGLOW',
                'nama_produk' => 'Wrdh cushion glow',
                'variants'    => [
                    ['label' => '11 C 15gr', 'harga_jual' => 110000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORITVALVET',
                'nama_produk' => 'Wrdh colorit valvet',
                'variants'    => [
                    ['label' => 'powder fondation refil 21 C', 'harga_jual' => 50000],
                    ['label' => 'powder fondation refil 33 W', 'harga_jual' => 50000],
                    ['label' => 'powder fondation refil 42 N', 'harga_jual' => 50000],
                    ['label' => 'powder fondation refil 31 C', 'harga_jual' => 50000],
                    ['label' => 'powder fondation 32 N', 'harga_jual' => 75000],
                    ['label' => 'powder fondation 43W', 'harga_jual' => 75000],
                    ['label' => 'powder fondation 11 C', 'harga_jual' => 75000],
                    ['label' => 'powder fondation 31C', 'harga_jual' => 75000],
                    ['label' => 'powder fondation 33 W', 'harga_jual' => 75000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORFITGLOW',
                'nama_produk' => 'Wrdh colorfit glow',
                'variants'    => [
                    ['label' => 'cushion 31W', 'harga_jual' => 115000],
                    ['label' => 'cushion 32N', 'harga_jual' => 115000],
                    ['label' => 'cushion 22N', 'harga_jual' => 115000],
                    ['label' => 'cushion 33W', 'harga_jual' => 115000],
                    ['label' => 'cushion 42N', 'harga_jual' => 115000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORFIT5D',
                'nama_produk' => 'Wrdh colorfit 5D',
                'variants'    => [
                    ['label' => 'cushion 23W', 'harga_jual' => 125000],
                    ['label' => 'cushion 31W', 'harga_jual' => 125000],
                    ['label' => 'cushion 32N', 'harga_jual' => 125000],
                    ['label' => 'cushion 33W', 'harga_jual' => 125000],
                    ['label' => 'cushion 21W', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'MAKEOVERMATTE',
                'nama_produk' => 'Make over matte',
                'variants'    => [
                    ['label' => 'powder N10', 'harga_jual' => 160000],
                    ['label' => 'powder W32', 'harga_jual' => 160000],
                    ['label' => 'powder W30', 'harga_jual' => 160000],
                    ['label' => 'powder W20', 'harga_jual' => 160000],
                    ['label' => 'powder W22', 'harga_jual' => 160000],
                ],
            ],
            [
                'kode_produk' => 'MAKEOVERHIDRASTAY',
                'nama_produk' => 'Make over hidrastay',
                'variants'    => [
                    ['label' => 'matte cushion W21', 'harga_jual' => 180000],
                    ['label' => 'cushion W12', 'harga_jual' => 180000],
                    ['label' => 'matte cushion N30', 'harga_jual' => 180000],
                    ['label' => 'matte cushion W32', 'harga_jual' => 180000],
                ],
            ],
            [
                'kode_produk' => 'MAKEOVERPOWERSTAY',
                'nama_produk' => 'Make over powerstay',
                'variants'    => [
                    ['label' => 'cushion W21', 'harga_jual' => 180000],
                    ['label' => 'cushion N20', 'harga_jual' => 180000],
                    ['label' => 'cushion W32', 'harga_jual' => 180000],
                    ['label' => 'lip matte', 'harga_jual' => 110000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLUMINOUSPOWDER',
                'nama_produk' => 'Wrdh luminous powder',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 40000],
                    ['label' => '02', 'harga_jual' => 40000],
                    ['label' => '03', 'harga_jual' => 40000],
                    ['label' => '04', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLUMINOUSTWE',
                'nama_produk' => 'Wrdh luminous twe',
                'variants'    => [
                    ['label' => 'cake 01', 'harga_jual' => 48000],
                    ['label' => 'cake 04', 'harga_jual' => 48000],
                    ['label' => 'cake refil 01', 'harga_jual' => 35000],
                    ['label' => 'cake refil 02', 'harga_jual' => 35000],
                    ['label' => 'cake refil 03', 'harga_jual' => 35000],
                    ['label' => 'cake refil 04', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'WRDHEXLUSIVETWO',
                'nama_produk' => 'Wrdh exlusive two',
                'variants'    => [
                    ['label' => 'way cake refil', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLUMINOSFONDATION',
                'nama_produk' => 'Wrdh luminos fondation',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 40000],
                    ['label' => '03', 'harga_jual' => 40000],
                    ['label' => '04', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLIGHTENINGBB',
                'nama_produk' => 'Wrdh lightening BB',
                'variants'    => [
                    ['label' => 'tin 30 ml 01', 'harga_jual' => 58000],
                    ['label' => 'tin 30 ml 02', 'harga_jual' => 58000],
                    ['label' => 'tin 30 ml 03', 'harga_jual' => 58000],
                    ['label' => 'tin 30 ml 04', 'harga_jual' => 58000],
                    ['label' => 'tin 25 ml 01', 'harga_jual' => 48000],
                    ['label' => 'tin 25 ml 02', 'harga_jual' => 48000],
                    ['label' => 'tin 25 ml 03', 'harga_jual' => 48000],
                    ['label' => 'tin 25 ml 04', 'harga_jual' => 48000],
                    ['label' => 'tin 25 ml 05', 'harga_jual' => 48000],
                    ['label' => 'tint 15 ml 01', 'harga_jual' => 33000],
                    ['label' => 'tint 15 ml 02', 'harga_jual' => 33000],
                    ['label' => 'tint 15 ml 03', 'harga_jual' => 33000],
                    ['label' => 'tint 15 ml 04', 'harga_jual' => 33000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLIGHTENINGPOWDER',
                'nama_produk' => 'Wrdh lightening powder',
                'variants'    => [
                    ['label' => '04', 'harga_jual' => 37000],
                    ['label' => '03', 'harga_jual' => 67000],
                    ['label' => '01', 'harga_jual' => 37000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLIGHTENINGPADAT',
                'nama_produk' => 'Wrdh lightening padat',
                'variants'    => [
                    ['label' => '12gr 01', 'harga_jual' => 55000],
                    ['label' => '12gr 02', 'harga_jual' => 50000],
                    ['label' => '12gr 03', 'harga_jual' => 50000],
                    ['label' => '12gr 04', 'harga_jual' => 55000],
                    ['label' => 'refil 01', 'harga_jual' => 35000],
                    ['label' => 'refil 02', 'harga_jual' => 35000],
                    ['label' => 'refil 03', 'harga_jual' => 35000],
                    ['label' => 'refil 04', 'harga_jual' => 35000],
                    ['label' => 'refil 05', 'harga_jual' => 35000],
                    ['label' => 'refil 06', 'harga_jual' => 35000],
                    ['label' => 'refil 07', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCUSHIONEXCLUSIF',
                'nama_produk' => 'Wrdh cushion exclusif',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 125000],
                    ['label' => '03', 'harga_jual' => 125000],
                ],
            ],
            [
                'kode_produk' => 'WRDHEXCLUSIFTWE',
                'nama_produk' => 'Wrdh Exclusif twe',
                'variants'    => [
                    ['label' => 'cake 01', 'harga_jual' => 90000],
                    ['label' => 'cake 02', 'harga_jual' => 90000],
                    ['label' => 'cake 03', 'harga_jual' => 90000],
                    ['label' => 'cake 04', 'harga_jual' => 90000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCUSHIONLITE',
                'nama_produk' => 'Wrdh cushion lite',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 95000],
                    ['label' => '02', 'harga_jual' => 95000],
                    ['label' => '03', 'harga_jual' => 95000],
                    ['label' => '04', 'harga_jual' => 95000],
                    ['label' => '05', 'harga_jual' => 95000],
                    ['label' => '06', 'harga_jual' => 95000],
                    ['label' => 'refil 01', 'harga_jual' => 70000],
                    ['label' => 'refil 02', 'harga_jual' => 70000],
                    ['label' => 'refil 03', 'harga_jual' => 70000],
                    ['label' => 'refil 04', 'harga_jual' => 70000],
                    ['label' => 'refil 05', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORFITMATTE',
                'nama_produk' => 'Wrdh colorfit matte',
                'variants'    => [
                    ['label' => 'fondation 22N', 'harga_jual' => 65000],
                    ['label' => 'fondation 23W', 'harga_jual' => 65000],
                    ['label' => 'fondation 32N', 'harga_jual' => 65000],
                    ['label' => 'fondation 33W', 'harga_jual' => 65000],
                    ['label' => 'fondation 11C', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORFITVALVET',
                'nama_produk' => 'Wrdh colorfit valvet',
                'variants'    => [
                    ['label' => 'powder foundation 42N', 'harga_jual' => 70000],
                    ['label' => 'powder foundation 43W', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIMOISRETINOL',
                'nama_produk' => 'Hanasui mois retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIMOISKUNING',
                'nama_produk' => 'Hanasui mois kuning',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 42000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMVIT',
                'nama_produk' => 'Hanasui serum VIT',
                'variants'    => [
                    ['label' => 'C', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMGOLD',
                'nama_produk' => 'Hanasui serum Gold',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMCOLAGEN',
                'nama_produk' => 'Hanasui serum colagen',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMRETINOL',
                'nama_produk' => 'Hanasui serum retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000],
                    ['label' => 'expert', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMPEELING',
                'nama_produk' => 'Hanasui serum peeling',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMBRIGHT',
                'nama_produk' => 'Hanasui serum bright',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                    ['label' => 'expert', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMBAKUCHIO',
                'nama_produk' => 'Hanasui serum bakuchiol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 26000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMBARIER',
                'nama_produk' => 'Hanasui serum barier',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMACNE',
                'nama_produk' => 'Hanasui serum acne',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISERUMMINIPORE',
                'nama_produk' => 'Hanasui serum minipore',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 26000],
                ],
            ],
            [
                'kode_produk' => 'HANASUINIGHCREAM',
                'nama_produk' => 'Hanasui nigh cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIDAYCREAM',
                'nama_produk' => 'Hanasui day cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIESSENCE',
                'nama_produk' => 'Hanasui essence',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIGENTLECLAENCE',
                'nama_produk' => 'Hanasui gentle claencer',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIPAKETANISI',
                'nama_produk' => 'Hanasui paketan isi',
                'variants'    => [
                    ['label' => '5', 'harga_jual' => 140000],
                ],
            ],
            [
                'kode_produk' => 'TISHALIPSTAIN',
                'nama_produk' => 'Tisha lip stain',
                'variants'    => [
                    ['label' => '02', 'harga_jual' => 25000],
                    ['label' => '03', 'harga_jual' => 25000],
                    ['label' => '05', 'harga_jual' => 25000],
                    ['label' => '07', 'harga_jual' => 25000],
                    ['label' => '09', 'harga_jual' => 25000],
                    ['label' => '10', 'harga_jual' => 25000],
                    ['label' => '12', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'TISHALIPCREAM',
                'nama_produk' => 'Tisha lip cream',
                'variants'    => [
                    ['label' => '01 fresh nude', 'harga_jual' => 30000],
                    ['label' => '02 coco dream', 'harga_jual' => 30000],
                    ['label' => '02 Beverly nude', 'harga_jual' => 30000],
                    ['label' => '03 catton candy', 'harga_jual' => 30000],
                    ['label' => '05 red berry', 'harga_jual' => 30000],
                    ['label' => '06 pink swet', 'harga_jual' => 30000],
                    ['label' => '06 elena red', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARILIPBALM',
                'nama_produk' => 'Purbasari lip balm',
                'variants'    => [
                    ['label' => 'red velvet', 'harga_jual' => 28000],
                    ['label' => 'strawberry crush', 'harga_jual' => 28000],
                    ['label' => 'orange blast', 'harga_jual' => 28000],
                    ['label' => 'avocado', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYLIPPROTECT',
                'nama_produk' => 'Facetology lip protector',
                'variants'    => [
                    ['label' => 'flavored', 'harga_jual' => 60000],
                    ['label' => 'unflavored', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'VASELINBIBIR',
                'nama_produk' => 'Vaselin bibir',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000],
                ],
            ],
            [
                'kode_produk' => 'TISHABALMBLOOM',
                'nama_produk' => 'Tisha balm bloom',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'LIPARABLOKAL',
                'nama_produk' => 'lip arab lokal',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 7000],
                ],
            ],
            [
                'kode_produk' => 'LIPARABORIGINAL',
                'nama_produk' => 'lip arab original',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000],
                ],
            ],
            [
                'kode_produk' => 'DOLBILIP165',
                'nama_produk' => 'dolbi lip 165',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'DOLBILIP171',
                'nama_produk' => 'dolbi lip 171',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'DOLBILIP154',
                'nama_produk' => 'dolbi lip 154',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'BRASOVLIPTINT',
                'nama_produk' => 'Brasov lip tint',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 20000],
                    ['label' => '03', 'harga_jual' => 20000],
                    ['label' => '04', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'LOMIRALIPSERUM',
                'nama_produk' => 'Lomira lip serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'TAZZILIPBOOSTER',
                'nama_produk' => 'Tazzi lip booster',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARILIPSERUM',
                'nama_produk' => 'Purbasari lip serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 26000],
                    ['label' => 'infused 01', 'harga_jual' => 36000],
                    ['label' => 'infused 02', 'harga_jual' => 36000],
                    ['label' => 'infused 06', 'harga_jual' => 36000],
                ],
            ],
            [
                'kode_produk' => 'SALSALIPGLOW',
                'nama_produk' => 'Salsa lip glow',
                'variants'    => [
                    ['label' => 'serum', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKPEPTIDE',
                'nama_produk' => 'Skintifik peptide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 100000],
                    ['label' => 'Default', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'SKINTIFIKLIPSERUM',
                'nama_produk' => 'Skintifik lip serum',
                'variants'    => [
                    ['label' => 'pink berry', 'harga_jual' => 100000],
                    ['label' => 'peach rose', 'harga_jual' => 100000],
                    ['label' => 'pink Cherry red', 'harga_jual' => 100000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSERUMPERFECTY',
                'nama_produk' => 'Wrdh serum perfecty',
                'variants'    => [
                    ['label' => 'VIT C 30 gr', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMOISNANO',
                'nama_produk' => 'Wrdh mois nano',
                'variants'    => [
                    ['label' => 'retinol 28 gr', 'harga_jual' => 53000],
                ],
            ],
            [
                'kode_produk' => 'WRDHBODYMIST',
                'nama_produk' => 'Wrdh body mist',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'WRDHREMOVER100',
                'nama_produk' => 'Wrdh remover 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'WRDHREMOVER50',
                'nama_produk' => 'Wrdh remover 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'COSRXFWMERAH',
                'nama_produk' => 'Cosrx FW merah',
                'variants'    => [
                    ['label' => '150 ml', 'harga_jual' => 125000],
                    ['label' => '50 ml', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'COSRXFWBIRU',
                'nama_produk' => 'Cosrx FW biru',
                'variants'    => [
                    ['label' => '150 ml', 'harga_jual' => 115000],
                ],
            ],
            [
                'kode_produk' => 'CETAPHILFW118',
                'nama_produk' => 'Cetaphil FW 118',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 85000],
                ],
            ],
            [
                'kode_produk' => 'CETAPHILFW236',
                'nama_produk' => 'Cetaphil FW 236',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 140000],
                ],
            ],
            [
                'kode_produk' => 'CETAPHILSUNCREEN',
                'nama_produk' => 'Cetaphil suncreen',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 262000],
                ],
            ],
            [
                'kode_produk' => 'YOUPEELING',
                'nama_produk' => 'YOU peeling',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'YOUFWHY',
                'nama_produk' => 'YOU FW hy',
                'variants'    => [
                    ['label' => 'amino niacinamide 100 gr', 'harga_jual' => 35000],
                    ['label' => 'amino glowing 50 ml', 'harga_jual' => 25000],
                    ['label' => 'amino wow tery 50 ml', 'harga_jual' => 25000],
                    ['label' => 'amino acne gel 100 gr', 'harga_jual' => 35000],
                    ['label' => 'amino ac -Ttack 50 gr', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'YOUFWBYBYETERIA',
                'nama_produk' => 'YOU FW by-byeteria',
                'variants'    => [
                    ['label' => '50gr', 'harga_jual' => 23000],
                    ['label' => '100 gr', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'YOUFWOIL',
                'nama_produk' => 'YOU FW oil',
                'variants'    => [
                    ['label' => 'control 100 gr', 'harga_jual' => 35000],
                    ['label' => 'control 50 gr', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'YOUFWCENTELA',
                'nama_produk' => 'YOU FW centela',
                'variants'    => [
                    ['label' => '100 g', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'LABOREGENTLEBIOME',
                'nama_produk' => 'Labore gentle biome',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 147000],
                ],
            ],
            [
                'kode_produk' => 'LABOREBIOMEREPAIR',
                'nama_produk' => 'Labore biome repair',
                'variants'    => [
                    ['label' => 'barier', 'harga_jual' => 155000],
                ],
            ],
            [
                'kode_produk' => 'LABOREMILKCLENCER',
                'nama_produk' => 'Labore milk clencer',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 85000],
                    ['label' => '50 ml', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'LABOREISI3',
                'nama_produk' => 'Labore isi 3',
                'variants'    => [
                    ['label' => 'mini', 'harga_jual' => 120000],
                ],
            ],
            [
                'kode_produk' => 'LABOREPHYSICALSUNCRE',
                'nama_produk' => 'Labore physical suncreen',
                'variants'    => [
                    ['label' => '10 ml', 'harga_jual' => 55000],
                    ['label' => '30 ml', 'harga_jual' => 145000],
                ],
            ],
            [
                'kode_produk' => 'LABOREACNE',
                'nama_produk' => 'Labore acne &',
                'variants'    => [
                    ['label' => 'oil correct physical suncreen 40 ml', 'harga_jual' => 150000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERFWBRIGHT',
                'nama_produk' => 'Garnier FW bright',
                'variants'    => [
                    ['label' => 'complek 100 ml', 'harga_jual' => 32000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERFWCLEAR',
                'nama_produk' => 'Garnier fw clear',
                'variants'    => [
                    ['label' => 'dullnes 100 ml', 'harga_jual' => 32000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERFWSCRUB',
                'nama_produk' => 'Garnier fw scrub',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERFWSAKURA',
                'nama_produk' => 'Garnier fw sakura',
                'variants'    => [
                    ['label' => 'glow ceramide whip 100 ml', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERSAKURASERUM',
                'nama_produk' => 'Garnier sakura serum',
                'variants'    => [
                    ['label' => 'cream 50 ml', 'harga_jual' => 82000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERSAKURASLEEPIN',
                'nama_produk' => 'Garnier sakura sleeping',
                'variants'    => [
                    ['label' => 'mask nigh hyaluron 20 ml', 'harga_jual' => 33000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERSAKURAREPARIN',
                'nama_produk' => 'Garnier sakura reparing',
                'variants'    => [
                    ['label' => 'serum 10X ceramide 30 ml', 'harga_jual' => 105000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERBRIGHTANTI',
                'nama_produk' => 'Garnier bright anti',
                'variants'    => [
                    ['label' => 'acne booster serum', 'harga_jual' => 110000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERBRIGHTCOMPLET',
                'nama_produk' => 'Garnier bright complete',
                'variants'    => [
                    ['label' => 'anti acne booster serum', 'harga_jual' => 135000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERNIGHSERUM',
                'nama_produk' => 'Garnier nigh serum',
                'variants'    => [
                    ['label' => '10 % pure VIT c 15 ml', 'harga_jual' => 67000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERBOOSTERSERUM',
                'nama_produk' => 'Garnier booster serum',
                'variants'    => [
                    ['label' => '30x VIT c 15 ml', 'harga_jual' => 82000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERVITC',
                'nama_produk' => 'Garnier VIT c',
                'variants'    => [
                    ['label' => 'water gel bright komplek 20 ml', 'harga_jual' => 34000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERBRIGHTCOMPLEK',
                'nama_produk' => 'Garnier bright complek',
                'variants'    => [
                    ['label' => 'vit c serum 20 ml', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERNIGHVIT',
                'nama_produk' => 'Garnier nigh VIT',
                'variants'    => [
                    ['label' => 'c sleeping mask 50 ml', 'harga_jual' => 88000],
                ],
            ],
            [
                'kode_produk' => 'HADALABOFWGOKU',
                'nama_produk' => 'Hadalabo Fw goku',
                'variants'    => [
                    ['label' => 'jyun 50 gr', 'harga_jual' => 27000],
                    ['label' => 'jyun 100 gr', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'HADALABOFWSHIRO',
                'nama_produk' => 'Hadalabo Fw shiro',
                'variants'    => [
                    ['label' => 'jyun 100 gr', 'harga_jual' => 40000],
                    ['label' => 'jyun 50 gr', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'MENTHOLATUMADBOTANIC',
                'nama_produk' => 'Mentholatum AD botanical',
                'variants'    => [
                    ['label' => 'lotion 150 gr', 'harga_jual' => 105000],
                ],
            ],
            [
                'kode_produk' => 'MENTHOLATUMADLOTION',
                'nama_produk' => 'Mentholatum AD lotion',
                'variants'    => [
                    ['label' => '150 gr', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'HADALABOMOIS3D',
                'nama_produk' => 'Hadalabo mois 3D',
                'variants'    => [
                    ['label' => 'gel 40 gr', 'harga_jual' => 102000],
                ],
            ],
            [
                'kode_produk' => 'HADALABOULTIMAWHITEN',
                'nama_produk' => 'Hadalabo ultima whitening',
                'variants'    => [
                    ['label' => 'cream 40 gr', 'harga_jual' => 75000],
                ],
            ],
            [
                'kode_produk' => 'SALSAELINER',
                'nama_produk' => 'Salsa eliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'DAZZELMEMAKARA',
                'nama_produk' => 'Dazzelme makara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'MYBELINEMASKARA',
                'nama_produk' => 'My beline maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000],
                ],
            ],
            [
                'kode_produk' => 'QLELINERSPIDOL',
                'nama_produk' => 'QL eliner spidol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'XIXIUELINER',
                'nama_produk' => 'Xi xiu eliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'BULUMATAMILAN',
                'nama_produk' => 'Bulu mata milan',
                'variants'    => [
                    ['label' => 'story', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'BULUMATAMAGEFY',
                'nama_produk' => 'bulu mata magefy',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'QLDRAMATIKPENSIL',
                'nama_produk' => 'QL dramatik pensil',
                'variants'    => [
                    ['label' => 'alis black', 'harga_jual' => 12000],
                    ['label' => 'alis dark brown', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'IMFLORAPENSILALIS',
                'nama_produk' => 'imflora pensil alis',
                'variants'    => [
                    ['label' => 'brown', 'harga_jual' => 8000],
                ],
            ],
            [
                'kode_produk' => 'SALSAMASKARAEVERLASH',
                'nama_produk' => 'Salsa maskara everlash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'ESSENSEYELINER',
                'nama_produk' => 'Essens eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'ESSENSMASKARA',
                'nama_produk' => 'Essens maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'QLEYELINERDRAMATIK',
                'nama_produk' => 'QL eyeliner dramatik',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'OMGMASKARA',
                'nama_produk' => 'OMG maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'AZZURAMASKARA',
                'nama_produk' => 'Azzura maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'OTOMASKARA2',
                'nama_produk' => 'O.T.O maskara 2',
                'variants'    => [
                    ['label' => 'in 1', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'SALSA2IN',
                'nama_produk' => 'Salsa 2 in',
                'variants'    => [
                    ['label' => '1 maskara & eyeliner', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'TIMEPHORIAEYELINER',
                'nama_produk' => 'Timephoria eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 90000],
                ],
            ],
            [
                'kode_produk' => 'QLMASKARATOP',
                'nama_produk' => 'QL maskara top',
                'variants'    => [
                    ['label' => 'brand', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIMASKARA',
                'nama_produk' => 'Hanasui maskara',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'PINKFLASHMASKARA',
                'nama_produk' => 'Pink flash maskara',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 25000],
                    ['label' => 'hitam', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'PINKFLASHEYEBROW',
                'nama_produk' => 'Pink flash eyebrow',
                'variants'    => [
                    ['label' => 'pensil', 'harga_jual' => 18000],
                ],
            ],
            [
                'kode_produk' => 'SALSAPENSILALIS',
                'nama_produk' => 'Salsa pensil alis',
                'variants'    => [
                    ['label' => 'black', 'harga_jual' => 5000],
                    ['label' => 'brown', 'harga_jual' => 5000],
                ],
            ],
            [
                'kode_produk' => 'XIUXIUPENSIL',
                'nama_produk' => 'Xiu xiu pensil',
                'variants'    => [
                    ['label' => 'alis black', 'harga_jual' => 8000],
                    ['label' => 'alis brown', 'harga_jual' => 8000],
                ],
            ],
            [
                'kode_produk' => 'TISHAEYELINER',
                'nama_produk' => 'Tisha eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHEYELINER',
                'nama_produk' => 'wrdh eyeliner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'JUSTMISKPENSIL',
                'nama_produk' => 'just misk pensil',
                'variants'    => [
                    ['label' => 'alis black', 'harga_jual' => 8000],
                    ['label' => 'alis brown', 'harga_jual' => 8000],
                ],
            ],
            [
                'kode_produk' => 'QLPRIMER',
                'nama_produk' => 'QL primer',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 45000],
                ],
            ],
            [
                'kode_produk' => 'WRDHPENSILALIS',
                'nama_produk' => 'wrdh pensil alis',
                'variants'    => [
                    ['label' => 'black', 'harga_jual' => 40000],
                    ['label' => 'eyeexpert brown', 'harga_jual' => 40000],
                    ['label' => 'eyeexpert hitam', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'IMPLORAPENSILALIS',
                'nama_produk' => 'implora pensil alis',
                'variants'    => [
                    ['label' => 'black', 'harga_jual' => 8000],
                    ['label' => 'silver', 'harga_jual' => 8000],
                    ['label' => 'brown', 'harga_jual' => 8000],
                ],
            ],
            [
                'kode_produk' => 'BRIDNEYPENSILALIS',
                'nama_produk' => 'bridney pensil alis',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 8000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIPENSILALIS',
                'nama_produk' => 'hanasui pensil alis',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'SILKYGIRSPENSIL',
                'nama_produk' => 'silky girs pensil',
                'variants'    => [
                    ['label' => 'alis 2in1', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'MYBABYMINYAK',
                'nama_produk' => 'my baby minyak',
                'variants'    => [
                    ['label' => 'Telon 150 ml lavender', 'harga_jual' => 35000],
                    ['label' => 'Telon 90ml', 'harga_jual' => 23000],
                    ['label' => 'Telon 60 ml', 'harga_jual' => 17000],
                    ['label' => 'Telon 30 ml', 'harga_jual' => 10000],
                ],
            ],
            [
                'kode_produk' => 'FRESHCARE10',
                'nama_produk' => 'fresh care 10',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 13000],
                ],
            ],
            [
                'kode_produk' => 'FRESHCARESMASH',
                'nama_produk' => 'fresh care smash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'MKPAYAM40',
                'nama_produk' => 'mkp ayam 40',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'MKPAYAM25',
                'nama_produk' => 'mkp ayam 25',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'MKPAYAM12',
                'nama_produk' => 'mkp ayam 12',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 10000],
                ],
            ],
            [
                'kode_produk' => 'MKPCAPLANG',
                'nama_produk' => 'mkp cap Lang',
                'variants'    => [
                    ['label' => '120 ml', 'harga_jual' => 43000],
                    ['label' => '60 ml', 'harga_jual' => 23000],
                    ['label' => '30 ml', 'harga_jual' => 13000],
                    ['label' => '15 ml', 'harga_jual' => 7000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARIMINYAKZAITU',
                'nama_produk' => 'purbasari minyak zaitun',
                'variants'    => [
                    ['label' => '150 ml jasmine', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'HERBORISTMINYAKZAITU',
                'nama_produk' => 'herborist minyak zaitun',
                'variants'    => [
                    ['label' => '75 ml', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'MUSTIKARATUMINYAK',
                'nama_produk' => 'mustika ratu minyak',
                'variants'    => [
                    ['label' => 'zaitun 175 ml', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'MUSTIKAMINYAKZAITUN',
                'nama_produk' => 'mustika minyak zaitun',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'SHUGAINYAKZAITUN',
                'nama_produk' => 'shuga inyak zaitun',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'TEMULAWAKTHEFACE',
                'nama_produk' => 'temulawak the face',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'SALSAREMOVERNAIL',
                'nama_produk' => 'salsa remover nail',
                'variants'    => [
                    ['label' => '100 gr', 'harga_jual' => 10000],
                    ['label' => '80 gr', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'YUCHUNMAI',
                'nama_produk' => 'yu Chun mai',
                'variants'    => [
                    ['label' => 'serum', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'SHDAY',
                'nama_produk' => 'SH day',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'SHSABUN',
                'nama_produk' => 'SH sabun',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000],
                ],
            ],
            [
                'kode_produk' => 'TAGLOWINGSERUM',
                'nama_produk' => 'TA glowing serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'TAGLOWINGTONER',
                'nama_produk' => 'TA glowing toner',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'MAXISUNCREEN',
                'nama_produk' => 'Maxi suncreen',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISUNSPF',
                'nama_produk' => 'hanasui sun spf',
                'variants'    => [
                    ['label' => '30 putih', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISPF50',
                'nama_produk' => 'hanasui spf 50',
                'variants'    => [
                    ['label' => 'biru', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'SCORASPF40',
                'nama_produk' => 'scora spf 40',
                'variants'    => [
                    ['label' => 'sun 40 gr', 'harga_jual' => 40000],
                    ['label' => 'sun 30 gr', 'harga_jual' => 45000],
                ],
            ],
            [
                'kode_produk' => 'SCORATONEUP',
                'nama_produk' => 'scora tone up',
                'variants'    => [
                    ['label' => '30 gr', 'harga_jual' => 35000],
                    ['label' => '20 gr', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'EMINASUNBATLECICA',
                'nama_produk' => 'emina sunbatle cica',
                'variants'    => [
                    ['label' => 'spf 35 biru', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'EMINASUNBATLESPF',
                'nama_produk' => 'emina sunbatle spf',
                'variants'    => [
                    ['label' => '50', 'harga_jual' => 52000],
                    ['label' => '35 oranye 20 ml', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'EMINASUNAIRI',
                'nama_produk' => 'emina sun airi',
                'variants'    => [
                    ['label' => 'uv spf 50 cica', 'harga_jual' => 34000],
                    ['label' => 'uv spf 50 pink', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'EMINATONEUP',
                'nama_produk' => 'emina tone up',
                'variants'    => [
                    ['label' => 'spf 15', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'AVIONESUNJEJU',
                'nama_produk' => 'avione sunjeju',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'GLOOWBE',
                'nama_produk' => 'gloow & be',
                'variants'    => [
                    ['label' => 'sun spf 40', 'harga_jual' => 40000],
                    ['label' => 'tone up 15 ml', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'IMPLORASUNSPF',
                'nama_produk' => 'implora sun spf',
                'variants'    => [
                    ['label' => '40 ungu', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYSUNTINTET',
                'nama_produk' => 'facetology sun tintet',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYSUNNORMAL',
                'nama_produk' => 'facetology sun normal',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYSUNOIL',
                'nama_produk' => 'facetology sun oil',
                'variants'    => [
                    ['label' => 'skin', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'YOUSUNBRELLAPINK',
                'nama_produk' => 'YOU sunbrella pink',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 75000],
                ],
            ],
            [
                'kode_produk' => 'YOUSUNBRELLAHIJAU',
                'nama_produk' => 'YOU sunbrella hijau',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                    ['label' => 'kecil', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'YOUSUNBRELLAORANYE',
                'nama_produk' => 'YOU sunbrella oranye',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'YOUSUNBRELLABIRU',
                'nama_produk' => 'YOU sunbrella biru',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000],
                ],
            ],
            [
                'kode_produk' => 'YOUSUNBRELLADAYLY',
                'nama_produk' => 'YOU sunbrella dayly',
                'variants'    => [
                    ['label' => 'defensi', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSUNUV',
                'nama_produk' => 'wrdh sun uv',
                'variants'    => [
                    ['label' => 'Shield spf 50 biru', 'harga_jual' => 50000],
                    ['label' => 'Shield spf 50 oranye', 'harga_jual' => 72000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSUNTONE',
                'nama_produk' => 'wrdh sun tone',
                'variants'    => [
                    ['label' => 'up pink', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSUNAIRY',
                'nama_produk' => 'wrdh sun airy',
                'variants'    => [
                    ['label' => 'smooth spf 50 centela', 'harga_jual' => 35000],
                    ['label' => 'smooth oil control', 'harga_jual' => 52000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSUNACTIVE',
                'nama_produk' => 'wrdh sun active',
                'variants'    => [
                    ['label' => 'protektion spf 50 oranye', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSUNTINTET',
                'nama_produk' => 'wrdh sun tintet',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSUNBRIGHT',
                'nama_produk' => 'wrdh sun bright',
                'variants'    => [
                    ['label' => 'c kuning', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'WRDHSUNACNE',
                'nama_produk' => 'wrdh sun acne',
                'variants'    => [
                    ['label' => 'calming spf 50', 'harga_jual' => 35000],
                    ['label' => 'calming spf 35', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'SKINAQUASUN',
                'nama_produk' => 'skin Aqua sun',
                'variants'    => [
                    ['label' => 'hijau', 'harga_jual' => 50000],
                    ['label' => 'putih', 'harga_jual' => 50000],
                    ['label' => 'pink', 'harga_jual' => 50000],
                    ['label' => 'biru', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'YOUSUNBRELLASPRAY',
                'nama_produk' => 'YOU sunbrella spray',
                'variants'    => [
                    ['label' => 'Bru', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'AZZURASUNORANYE',
                'nama_produk' => 'Azzura sun oranye',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 32000],
                ],
            ],
            [
                'kode_produk' => 'OMGSUNSPF',
                'nama_produk' => 'OMG sun spf',
                'variants'    => [
                    ['label' => '50 tone up', 'harga_jual' => 25000],
                    ['label' => '50 acne oil control', 'harga_jual' => 26000],
                ],
            ],
            [
                'kode_produk' => 'AZARINESUNHIJAU',
                'nama_produk' => 'Azarine sun hijau',
                'variants'    => [
                    ['label' => '30 ml hydrasoothe', 'harga_jual' => 37000],
                ],
            ],
            [
                'kode_produk' => 'AZARINESUNCALMING',
                'nama_produk' => 'Azarine sun calming',
                'variants'    => [
                    ['label' => 'acne hijau', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'AZARINESUNHYDRAMAXC',
                'nama_produk' => 'Azarine sun hydramax-c',
                'variants'    => [
                    ['label' => 'oranye', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'AZARINESUNCICA',
                'nama_produk' => 'Azarine sun cica',
                'variants'    => [
                    ['label' => 'mide berier', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'AMATERASUNSPF35',
                'nama_produk' => 'Amaterasun spf 35',
                'variants'    => [
                    ['label' => 'all skin tipe', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'AMATERASUNSPF50',
                'nama_produk' => 'Amaterasun spf 50',
                'variants'    => [
                    ['label' => 'physical', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'AMATERASUNSKINTINTSA',
                'nama_produk' => 'Amaterasun skintint sand',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000],
                ],
            ],
            [
                'kode_produk' => 'AMATERASUNSKINTINTLI',
                'nama_produk' => 'Amaterasun skintint light',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 68000],
                ],
            ],
            [
                'kode_produk' => 'KAHFSUNSPF',
                'nama_produk' => 'Kahf sun spf',
                'variants'    => [
                    ['label' => '30', 'harga_jual' => 36000],
                ],
            ],
            [
                'kode_produk' => 'OMGFWBRIGHT',
                'nama_produk' => 'OMG fw bright',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 22000],
                    ['label' => '50 ml', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'OMGFWPEACH',
                'nama_produk' => 'OMG fw peach',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'OMGFWSALICYLACID',
                'nama_produk' => 'OMG fw salicylacid',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'OMGFWNIACINAMIDE',
                'nama_produk' => 'OMG fw niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HIMALAYAOILCONTROL',
                'nama_produk' => 'Himalaya oil control',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'HIMALAYAEXFOLIATING5',
                'nama_produk' => 'Himalaya exfoliating 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'HIMALAYAPURIFYING50',
                'nama_produk' => 'Himalaya purifying 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'HIMALAYAVITC',
                'nama_produk' => 'Himalaya VIT C',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'AZZURAFWWHIP',
                'nama_produk' => 'Azzura fw whip',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'AZZURANIGHCREAM',
                'nama_produk' => 'Azzura nigh cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'AZZURADAYCREAM',
                'nama_produk' => 'Azzura day cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'AZZURASERUM',
                'nama_produk' => 'Azzura serum',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'VIVAMASSAGE',
                'nama_produk' => 'Viva massage',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'VIVASKINFOOD',
                'nama_produk' => 'Viva skinfood',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'VIVASUNFOUNDATION',
                'nama_produk' => 'Viva sun foundation',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'VIVALIQUIDFONDATION',
                'nama_produk' => 'Viva liquid fondation',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 10000],
                ],
            ],
            [
                'kode_produk' => 'VIVAWHITENINGCREAM',
                'nama_produk' => 'Viva whitening cream',
                'variants'    => [
                    ['label' => '15 gr', 'harga_jual' => 15000],
                    ['label' => '40 gr', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'VIVANIGHCREAM',
                'nama_produk' => 'Viva nigh cream',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMOISNIACINAMIDE',
                'nama_produk' => 'Emina mois niacinamide',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMOISCALMING',
                'nama_produk' => 'Emina mois calming',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMOISACNE',
                'nama_produk' => 'Emina mois acne',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMOISRETINOL',
                'nama_produk' => 'Emina mois retinol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMOISBARIER',
                'nama_produk' => 'Emina mois barier',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'EMINAFWNIACINAMIDE',
                'nama_produk' => 'Emina FW niacinamide',
                'variants'    => [
                    ['label' => '50 ml', 'harga_jual' => 22000],
                    ['label' => '100 ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'EMINAFWPREBIOTIC',
                'nama_produk' => 'Emina fw prebiotic',
                'variants'    => [
                    ['label' => '100 ml', 'harga_jual' => 30000],
                    ['label' => '50 ml', 'harga_jual' => 22000],
                ],
            ],
            [
                'kode_produk' => 'EMINAFWACNE',
                'nama_produk' => 'Emina fw acne',
                'variants'    => [
                    ['label' => 'hijau', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'EMINAFWBERIER',
                'nama_produk' => 'Emina fw berier',
                'variants'    => [
                    ['label' => 'biru', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'EMINAFWBRIGHT',
                'nama_produk' => 'Emina fw bright',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 35000],
                    ['label' => 'stuff', 'harga_jual' => 18000],
                    ['label' => 'for acne', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMOISAM',
                'nama_produk' => 'Emina mois A.M',
                'variants'    => [
                    ['label' => 'glow up', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYFW100',
                'nama_produk' => 'Facetology FW 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 55000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYFW30',
                'nama_produk' => 'Facetology FW 30',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYMOIS',
                'nama_produk' => 'Facetology mois',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'SCORAFWPANTHENOL',
                'nama_produk' => 'Scora fw panthenol',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'SCORAFWSALICYLIC',
                'nama_produk' => 'Scora fw salicylic',
                'variants'    => [
                    ['label' => 'acid', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMOISBRIGHT',
                'nama_produk' => 'Emina mois bright',
                'variants'    => [
                    ['label' => 'stuff', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'PONDSFWBIOME',
                'nama_produk' => 'Pond\'s FW biome',
                'variants'    => [
                    ['label' => 'gel', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'PONDSFWNIASORCINOL',
                'nama_produk' => 'Pond\'s fw niasorcinol',
                'variants'    => [
                    ['label' => '50 gr', 'harga_jual' => 25000],
                    ['label' => '100 gr', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'PONDSFWCHARCOAL',
                'nama_produk' => 'Pond\'s fw charcoal',
                'variants'    => [
                    ['label' => '50 gr', 'harga_jual' => 25000],
                    ['label' => '100 gr', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'PONDSSERUMDOBLE',
                'nama_produk' => 'Pond\'s serum Doble',
                'variants'    => [
                    ['label' => 'action', 'harga_jual' => 85000],
                ],
            ],
            [
                'kode_produk' => 'PONDSSERUMNIGHT',
                'nama_produk' => 'Pond\'s serum night',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 80000],
                ],
            ],
            [
                'kode_produk' => 'PONDSWHIPCREAM',
                'nama_produk' => 'Pond\'s whip cream',
                'variants'    => [
                    ['label' => 'hexy retinol', 'harga_jual' => 170000],
                ],
            ],
            [
                'kode_produk' => 'PONDSNIGHCREAM',
                'nama_produk' => 'Pond\'s nigh cream',
                'variants'    => [
                    ['label' => '50 gr', 'harga_jual' => 180000],
                    ['label' => '9 gr', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'FAIRLOVELYCREAM',
                'nama_produk' => 'Fair lovely cream',
                'variants'    => [
                    ['label' => '23 gr', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'FAIRLOVELYFW',
                'nama_produk' => 'Fair lovely fw',
                'variants'    => [
                    ['label' => 'derma glow 100 gr', 'harga_jual' => 28000],
                    ['label' => 'derma glow 50 gr', 'harga_jual' => 18000],
                ],
            ],
            [
                'kode_produk' => 'FAIRLOVELYSUNCREEN',
                'nama_produk' => 'Fair lovely suncreen',
                'variants'    => [
                    ['label' => '40 gr', 'harga_jual' => 30000],
                    ['label' => '20 gr', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARIEXFOLIATING',
                'nama_produk' => 'Purbasari exfoliating',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARISCRUB200',
                'nama_produk' => 'Purbasari scrub 200',
                'variants'    => [
                    ['label' => 'gr', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARISCRUB100',
                'nama_produk' => 'Purbasari scrub 100',
                'variants'    => [
                    ['label' => 'gr', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'HANASUISCRUB',
                'nama_produk' => 'Hanasui scrub',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 22000],
                ],
            ],
            [
                'kode_produk' => 'MARINASCRUB',
                'nama_produk' => 'Marina scrub',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIEXFOLIATINGGE',
                'nama_produk' => 'Hanasui exfoliating gel',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 22000],
                ],
            ],
            [
                'kode_produk' => 'AYUDYALULURPENGANTIN',
                'nama_produk' => 'Ayudya lulur pengantin',
                'variants'    => [
                    ['label' => '1000 gr', 'harga_jual' => 47000],
                    ['label' => '300 gr', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARILULURPENGAN',
                'nama_produk' => 'Purbasari lulur pengantin',
                'variants'    => [
                    ['label' => '500 gr', 'harga_jual' => 30000],
                    ['label' => '1000 gr', 'harga_jual' => 48000],
                ],
            ],
            [
                'kode_produk' => 'SALSAEXFOLIATINGGEL',
                'nama_produk' => 'Salsa exfoliating gel',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'AZZURAMW',
                'nama_produk' => 'Azzura MW',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'PIXYMW',
                'nama_produk' => 'pixy MW',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'FACETOLOGYMW300',
                'nama_produk' => 'Facetology MW 300',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 45000],
                ],
            ],
            [
                'kode_produk' => 'SCORAMWBIRU',
                'nama_produk' => 'Scora MW biru',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'SILKYGIRLMW105',
                'nama_produk' => 'Silkygirl MW 105',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'OMGMW300',
                'nama_produk' => 'OMG MW 300',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'OMGMW65',
                'nama_produk' => 'OMG MW 65',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 16000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMW125',
                'nama_produk' => 'Emina MW 125',
                'variants'    => [
                    ['label' => 'ml hijau', 'harga_jual' => 33000],
                ],
            ],
            [
                'kode_produk' => 'EMINAMW300',
                'nama_produk' => 'Emina MW 300',
                'variants'    => [
                    ['label' => 'ml hijau', 'harga_jual' => 47000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIMW300',
                'nama_produk' => 'Hanasui MW 300',
                'variants'    => [
                    ['label' => 'ml hijau', 'harga_jual' => 43000],
                ],
            ],
            [
                'kode_produk' => 'HANASUIMW100',
                'nama_produk' => 'Hanasui MW 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 27000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMWLIGHTENING',
                'nama_produk' => 'Wrdh MW lightening',
                'variants'    => [
                    ['label' => '55 ml', 'harga_jual' => 38000],
                    ['label' => 'niacinamide 240 ml', 'harga_jual' => 70000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMWACNE',
                'nama_produk' => 'Wrdh MW acne',
                'variants'    => [
                    ['label' => 'derm 100 ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMWNATURE',
                'nama_produk' => 'Wrdh MW nature',
                'variants'    => [
                    ['label' => 'dayly 3x 100 ml', 'harga_jual' => 30000],
                    ['label' => 'dayly penthanol 100 ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'WRDHMWNIGHTTONE',
                'nama_produk' => 'Wrdh MW night+tone',
                'variants'    => [
                    ['label' => 'up isi dua pink', 'harga_jual' => 63000],
                ],
            ],
            [
                'kode_produk' => 'POSHBODYMIST',
                'nama_produk' => 'Posh body mist',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'PUTERIBODYSPLASH',
                'nama_produk' => 'Puteri body splash',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'SUMBERAYU90',
                'nama_produk' => 'Sumber ayu 90',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'SUMBERAYU50',
                'nama_produk' => 'Sumber ayu 50',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'OVALE2IN',
                'nama_produk' => 'Ovale 2 in',
                'variants'    => [
                    ['label' => '1 luminos 200 ml', 'harga_jual' => 28000],
                    ['label' => '1 luminos 100 ml', 'harga_jual' => 18000],
                    ['label' => '1 oil control 100 ml', 'harga_jual' => 18000],
                    ['label' => '1 oil control 200 ml', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERMWBHA',
                'nama_produk' => 'Garnier MW BHA',
                'variants'    => [
                    ['label' => 'biru 125 ml', 'harga_jual' => 35000],
                    ['label' => 'biru 50 ml', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERMWFOR',
                'nama_produk' => 'Garnier MW for',
                'variants'    => [
                    ['label' => 'sensitif pink 125 ml', 'harga_jual' => 35000],
                    ['label' => 'sensitif 50 ml', 'harga_jual' => 23000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERMWCLEANSING',
                'nama_produk' => 'Garnier MW cleansing',
                'variants'    => [
                    ['label' => 'oil 125 ml', 'harga_jual' => 50000],
                    ['label' => 'oil 50 ml', 'harga_jual' => 30000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERMWROSE',
                'nama_produk' => 'Garnier MW rose',
                'variants'    => [
                    ['label' => 'water 400 ml', 'harga_jual' => 95000],
                    ['label' => 'water 125 ml', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERMWSALICILYK',
                'nama_produk' => 'Garnier MW salicilyk',
                'variants'    => [
                    ['label' => 'BHA 400 ml', 'harga_jual' => 105000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERMWPHAAHA',
                'nama_produk' => 'Garnier MW PHA+AHA',
                'variants'    => [
                    ['label' => '400 ml', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'GARNIERVITAMINC',
                'nama_produk' => 'Garnier vitamin c',
                'variants'    => [
                    ['label' => '400 ml', 'harga_jual' => 95000],
                ],
            ],
            [
                'kode_produk' => 'VIVAWHITESLEEPING',
                'nama_produk' => 'Viva white sleeping',
                'variants'    => [
                    ['label' => 'mask 80 gr', 'harga_jual' => 25000],
                ],
            ],
            [
                'kode_produk' => 'VIVAWHITEALOE',
                'nama_produk' => 'Viva white Aloe',
                'variants'    => [
                    ['label' => 'gel mois 80 gr', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARISIRIHKOTAK',
                'nama_produk' => 'Purbasari sirih kotak',
                'variants'    => [
                    ['label' => '60 ml', 'harga_jual' => 13000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARISIRIH60',
                'nama_produk' => 'Purbasari sirih 60',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 12000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARISIRIH125',
                'nama_produk' => 'Purbasari sirih 125',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 17000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARIFEMINIM100',
                'nama_produk' => 'Purbasari feminim 100',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'PURBASARIFEMINIM60',
                'nama_produk' => 'Purbasari feminim 60',
                'variants'    => [
                    ['label' => 'ml', 'harga_jual' => 15000],
                ],
            ],
            [
                'kode_produk' => 'BARENBLISSLIPTINT',
                'nama_produk' => 'Barenbliss lip tint',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 65000],
                    ['label' => '02', 'harga_jual' => 65000],
                    ['label' => '03', 'harga_jual' => 65000],
                    ['label' => '04', 'harga_jual' => 65000],
                    ['label' => '05', 'harga_jual' => 65000],
                    ['label' => '06', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'BARENBLISSLIPMOIS',
                'nama_produk' => 'Barenbliss lip mois',
                'variants'    => [
                    ['label' => 'tint 01', 'harga_jual' => 65000],
                    ['label' => 'tint 02', 'harga_jual' => 65000],
                    ['label' => 'tint 03', 'harga_jual' => 65000],
                    ['label' => 'tint 04', 'harga_jual' => 65000],
                    ['label' => 'tint 05', 'harga_jual' => 65000],
                    ['label' => 'tint 06', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'AZZURALIPMATTE',
                'nama_produk' => 'Azzura lip matte',
                'variants'    => [
                    ['label' => 'cream', 'harga_jual' => 45000],
                ],
            ],
            [
                'kode_produk' => 'AZZURAMATTELIPSTIK',
                'nama_produk' => 'Azzura matte lipstik',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'AZZURALIPVALVET',
                'nama_produk' => 'Azzura lip valvet',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'AZZURAJELLYLIP',
                'nama_produk' => 'Azzura jelly lip',
                'variants'    => [
                    ['label' => 'tint 01', 'harga_jual' => 35000],
                    ['label' => 'tint 07', 'harga_jual' => 35000],
                    ['label' => 'tint 08', 'harga_jual' => 35000],
                    ['label' => 'tint 05', 'harga_jual' => 35000],
                    ['label' => 'tint 06', 'harga_jual' => 35000],
                    ['label' => 'tint 04', 'harga_jual' => 35000],
                ],
            ],
            [
                'kode_produk' => 'TIMEPHORIALIPGLOSS',
                'nama_produk' => 'Timephoria lip gloss',
                'variants'    => [
                    ['label' => '001', 'harga_jual' => 90000],
                    ['label' => '005', 'harga_jual' => 90000],
                    ['label' => '012', 'harga_jual' => 90000],
                ],
            ],
            [
                'kode_produk' => 'TIMEPHORIALIPSTAIN',
                'nama_produk' => 'Timephoria lip stain',
                'variants'    => [
                    ['label' => '07', 'harga_jual' => 105000],
                ],
            ],
            [
                'kode_produk' => 'TIMEPHORIALIPMATTE',
                'nama_produk' => 'Timephoria lip matte',
                'variants'    => [
                    ['label' => '06', 'harga_jual' => 105000],
                    ['label' => '05', 'harga_jual' => 105000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLIPBALM',
                'nama_produk' => 'Wrdh lip balm',
                'variants'    => [
                    ['label' => 'pink', 'harga_jual' => 28000],
                    ['label' => 'orange', 'harga_jual' => 28000],
                ],
            ],
            [
                'kode_produk' => 'LTPROLIP',
                'nama_produk' => 'LT pro lip',
                'variants'    => [
                    ['label' => 'matte 01', 'harga_jual' => 105000],
                    ['label' => 'matte 02', 'harga_jual' => 105000],
                    ['label' => 'matte 04', 'harga_jual' => 105000],
                    ['label' => 'matte 05', 'harga_jual' => 105000],
                    ['label' => 'matte 10', 'harga_jual' => 105000],
                    ['label' => 'matte 08', 'harga_jual' => 105000],
                    ['label' => 'matte 07', 'harga_jual' => 105000],
                    ['label' => 'matte 09', 'harga_jual' => 105000],
                    ['label' => 'matte 13', 'harga_jual' => 105000],
                ],
            ],
            [
                'kode_produk' => 'PIXYLIPVINYL',
                'nama_produk' => 'Pixy lip vinyl',
                'variants'    => [
                    ['label' => '06', 'harga_jual' => 60000],
                    ['label' => '08', 'harga_jual' => 60000],
                ],
            ],
            [
                'kode_produk' => 'DAZZELMELOPVINYL',
                'nama_produk' => 'Dazzelme lop vinyl',
                'variants'    => [
                    ['label' => 'ink 099', 'harga_jual' => 38000],
                    ['label' => 'ink 035', 'harga_jual' => 38000],
                    ['label' => 'ink 008', 'harga_jual' => 38000],
                    ['label' => 'ink 555', 'harga_jual' => 38000],
                ],
            ],
            [
                'kode_produk' => 'AZZURALIPLONG',
                'nama_produk' => 'Azzura lip long',
                'variants'    => [
                    ['label' => 'lasting 03', 'harga_jual' => 32000],
                    ['label' => 'lasting 12', 'harga_jual' => 32000],
                    ['label' => 'lasting 06', 'harga_jual' => 32000],
                    ['label' => 'lasting 11', 'harga_jual' => 32000],
                    ['label' => 'lasting 09', 'harga_jual' => 32000],
                    ['label' => 'lasting 08', 'harga_jual' => 32000],
                ],
            ],
            [
                'kode_produk' => 'EMINAJELLYSTAIN',
                'nama_produk' => 'Emina jelly stain',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 50000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORFITLIP',
                'nama_produk' => 'Wrdh colorfit lip',
                'variants'    => [
                    ['label' => 'mousse', 'harga_jual' => 65000],
                ],
            ],
            [
                'kode_produk' => 'WRDHLIPGLASTING',
                'nama_produk' => 'Wrdh lip glasting',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 75000],
                    ['label' => '02', 'harga_jual' => 75000],
                    ['label' => '03', 'harga_jual' => 75000],
                    ['label' => '07', 'harga_jual' => 75000],
                    ['label' => '09', 'harga_jual' => 75000],
                    ['label' => '10', 'harga_jual' => 75000],
                    ['label' => '18', 'harga_jual' => 75000],
                    ['label' => '17', 'harga_jual' => 75000],
                    ['label' => '15', 'harga_jual' => 75000],
                    ['label' => '14', 'harga_jual' => 75000],
                ],
            ],
            [
                'kode_produk' => 'WRDHEVERYDAY',
                'nama_produk' => 'Wrdh every day',
                'variants'    => [
                    ['label' => 'lip shot 02', 'harga_jual' => 40000],
                    ['label' => 'lip shot 03', 'harga_jual' => 40000],
                    ['label' => 'lip shot 04', 'harga_jual' => 40000],
                    ['label' => 'lip shot 05', 'harga_jual' => 40000],
                    ['label' => 'lip shot 01', 'harga_jual' => 40000],
                ],
            ],
            [
                'kode_produk' => 'WRDHCOLORFITULTRA',
                'nama_produk' => 'Wrdh colorfit ultra',
                'variants'    => [
                    ['label' => 'light matte 13', 'harga_jual' => 42000],
                    ['label' => 'light matte 12', 'harga_jual' => 42000],
                    ['label' => 'light matte 11', 'harga_jual' => 42000],
                    ['label' => 'light matte 10', 'harga_jual' => 42000],
                    ['label' => 'light matte 09', 'harga_jual' => 42000],
                ],
            ],
            [
                'kode_produk' => 'MAKEOVERLIP',
                'nama_produk' => 'Make over lip',
                'variants'    => [
                    ['label' => 'glazed', 'harga_jual' => 115000],
                ],
            ],
            [
                'kode_produk' => 'MAKEOVERCOLOR',
                'nama_produk' => 'Make over color',
                'variants'    => [
                    ['label' => 'hypnose lip matte', 'harga_jual' => 80000],
                ],
            ],
            [
                'kode_produk' => 'IMFLORALIPSTIK',
                'nama_produk' => 'Imflora lipstik',
                'variants'    => [
                    ['label' => 'Default', 'harga_jual' => 20000],
                ],
            ],
            [
                'kode_produk' => 'HANASUILIPCREAM',
                'nama_produk' => 'Hanasui lip cream',
                'variants'    => [
                    ['label' => '01', 'harga_jual' => 23000],
                    ['label' => '02', 'harga_jual' => 23000],
                    ['label' => '03', 'harga_jual' => 23000],
                    ['label' => '04', 'harga_jual' => 23000],
                    ['label' => '05', 'harga_jual' => 23000],
                    ['label' => '06', 'harga_jual' => 23000],
                    ['label' => '07', 'harga_jual' => 23000],
                    ['label' => '08', 'harga_jual' => 23000],
                    ['label' => '09', 'harga_jual' => 23000],
                    ['label' => '10', 'harga_jual' => 23000],
                    ['label' => '11', 'harga_jual' => 23000],
                    ['label' => '12', 'harga_jual' => 23000],
                    ['label' => '13', 'harga_jual' => 23000],
                    ['label' => '14', 'harga_jual' => 0],
                ],
            ],
        ];

        // Attribute generik "Varian" sebagai wadah label varian produk
        $attrVarian = Attribute::firstOrCreate(
            ['kode' => 'varian'],
            ['nama' => 'Varian', 'urutan' => 1, 'store_id' => null]
        );

        foreach ($products as $p) {
            $kode = $p['kode_produk'];
            // Skip jika kode sudah ada
            if (Product::where('kode_produk', $kode)->exists()) continue;

            $product = Product::create([
                'store_id'    => 1,
                'kode_produk' => $kode,
                'nama_produk' => $p['nama_produk'],
            ]);

            foreach ($p['variants'] as $v) {
                $sku     = $kode . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
                $barcode = 'PRD' . str_pad($seq, 9, '0', STR_PAD_LEFT);

                $variant = ProductVariant::create([
                    'product_id'  => $product->id,
                    'sku'         => $sku,
                    'barcode'     => $barcode,
                    'harga_jual'  => $v['harga_jual'],
                    'is_active'   => 'Y',
                ]);

                $variant->barcodes()->create(['barcode' => $barcode]);

                // lakukan pengecekan apakah label varian sudah ada di AttributeValue, jika sudah gunakan yang lama, jika belum buat baru
                $attrValue = AttributeValue::where('attribute_id', $attrVarian->id)
                    ->where('nama', $v['label'])
                    ->first();
                if (!$attrValue) {
                    // Simpan label varian sebagai AttributeValue lalu link ke VariantAttribute
                    $attrValue = AttributeValue::create([
                        'attribute_id' => $attrVarian->id,
                        'store_id'     => 1,
                        'kode'         => 'V' . str_pad($seq, 6, '0', STR_PAD_LEFT),
                        'nama'         => $v['label'],
                        'urutan'       => $seq,
                    ]);
                }

                VariantAttribute::create([
                    'product_variant_id' => $variant->id,
                    'attribute_id'       => $attrVarian->id,
                    'attribute_value_id' => $attrValue->id,
                ]);

                $seq++;
            }
        }
    }
}
