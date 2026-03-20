<?php


class Pagination
{
    private int $total;
    private int $parPage;
    private int $pageCourante;
    private string $parametre;

    
    public function __construct(int $total, int $parPage = 9, string $parametre = 'p')
    {
        $this->total      = max(0, $total);
        $this->parPage    = max(1, $parPage);
        $this->parametre  = $parametre;

        $totalPages = $this->totalPages();
        $page = (int)($_GET[$parametre] ?? 1);
        $this->pageCourante = max(1, min($page, max(1, $totalPages)));
    }

    
    public function offset(): int
    {
        return ($this->pageCourante - 1) * $this->parPage;
    }

    
    public function limit(): int
    {
        return $this->parPage;
    }

    
    public function pageCourante(): int
    {
        return $this->pageCourante;
    }

    
    public function totalPages(): int
    {
        if ($this->total === 0) {
            return 1;
        }
        return (int)ceil($this->total / $this->parPage);
    }

    
    public function total(): int
    {
        return $this->total;
    }

    public function aPageSuivante(): bool
    {
        return $this->pageCourante < $this->totalPages();
    }

    public function aPagePrecedente(): bool
    {
        return $this->pageCourante > 1;
    }

    
    public function construireUrl(int $page): string
    {
        $params = $_GET;
        unset($params['ajax']);
        $params[$this->parametre] = $page;
        return '?' . http_build_query($params);
    }

    
    public function rendrePagination(): string
    {
        $total = $this->totalPages();

        if ($total <= 1) {
            return '';
        }

        $html = '<div class="pagination-controles">';

        
        if ($this->aPagePrecedente()) {
            $html .= '<a href="' . htmlspecialchars($this->construireUrl($this->pageCourante - 1)) . '" '
                   . 'class="pagination-btn pagination-precedent" aria-label="Page précédente">'
                   . '<i class="fas fa-chevron-left"></i> Précédent'
                   . '</a>';
        } else {
            $html .= '<span class="pagination-btn pagination-precedent pagination-desactive" aria-disabled="true">'
                   . '<i class="fas fa-chevron-left"></i> Précédent'
                   . '</span>';
        }

        
        $html .= '<div class="pagination-numeros">';
        $pages = $this->calculerPagesAffichees($total);

        $dernierePage = null;
        foreach ($pages as $p) {
            if ($dernierePage !== null && $p > $dernierePage + 1) {
                $html .= '<span class="pagination-ellipsis">…</span>';
            }

            if ($p === $this->pageCourante) {
                $html .= '<span class="pagination-btn pagination-active" aria-current="page">' . $p . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($this->construireUrl($p)) . '" '
                       . 'class="pagination-btn" aria-label="Page ' . $p . '">' . $p . '</a>';
            }

            $dernierePage = $p;
        }

        $html .= '</div>';

        
        if ($this->aPageSuivante()) {
            $html .= '<a href="' . htmlspecialchars($this->construireUrl($this->pageCourante + 1)) . '" '
                   . 'class="pagination-btn pagination-suivant" aria-label="Page suivante">'
                   . 'Suivant <i class="fas fa-chevron-right"></i>'
                   . '</a>';
        } else {
            $html .= '<span class="pagination-btn pagination-suivant pagination-desactive" aria-disabled="true">'
                   . 'Suivant <i class="fas fa-chevron-right"></i>'
                   . '</span>';
        }

        $html .= '</div>';
        return $html;
    }

    
    public function rendrePaginationComplete(): string
    {
        $controles = $this->rendrePagination();
        $totalPages = $this->totalPages();
        $count = $this->total();

        $infoPage = $totalPages > 1
            ? 'Page ' . $this->pageCourante() . ' sur ' . $totalPages . ' — '
            : '';
        $infoTotal = '<span class="pagination-total">'
            . $count . ' résultat' . ($count > 1 ? 's' : '')
            . '</span>';

        return '<nav class="pagination-nav" aria-label="Navigation par pages">'
            . $controles
            . '<p class="pagination-info">' . $infoPage . $infoTotal . '</p>'
            . '</nav>';
    }

    
    private function calculerPagesAffichees(int $total): array
    {
        if ($total <= 7) {
            return range(1, $total);
        }

        $p = $this->pageCourante;
        $pages = [1, $total];

        
        for ($i = max(2, $p - 1); $i <= min($total - 1, $p + 1); $i++) {
            $pages[] = $i;
        }

        $pages = array_unique($pages);
        sort($pages);
        return $pages;
    }
}
