<?php
namespace BAF;

class CMS {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    public function get_page($slug) {
        $stmt = $this->core->db()->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function get_all_pages($include_drafts = false) {
        $sql = "SELECT * FROM pages";
        if (!$include_drafts) {
            $sql .= " WHERE status = 'published'";
        }
        $sql .= " ORDER BY created_at DESC";
        return $this->core->db()->query($sql)->fetchAll();
    }

    public function create_page($data) {
        $stmt = $this->core->db()->prepare("INSERT INTO pages (slug, title, content, meta_title, meta_description, meta_keywords, is_external, external_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['slug'],
            $data['title'],
            $data['content'] ?? '',
            $data['meta_title'] ?? '',
            $data['meta_description'] ?? '',
            $data['meta_keywords'] ?? '',
            $data['is_external'] ?? 0,
            $data['external_url'] ?? '',
            $data['status'] ?? 'published'
        ]);
    }

    public function update_page($id, $data) {
        $stmt = $this->core->db()->prepare("UPDATE pages SET slug = ?, title = ?, content = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, is_external = ?, external_url = ?, status = ? WHERE id = ?");
        return $stmt->execute([
            $data['slug'],
            $data['title'],
            $data['content'] ?? '',
            $data['meta_title'] ?? '',
            $data['meta_description'] ?? '',
            $data['meta_keywords'] ?? '',
            $data['is_external'] ?? 0,
            $data['external_url'] ?? '',
            $data['status'] ?? 'published',
            $id
        ]);
    }

    public function delete_page($id) {
        $stmt = $this->core->db()->prepare("DELETE FROM pages WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
